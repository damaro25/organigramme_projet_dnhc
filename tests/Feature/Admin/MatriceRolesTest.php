<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Matrice d'accès aux pages de l'administration, rôle par rôle (comptes de démonstration).
 * Les droits plus fins (périmètre d'une section, projets affectés) sont testés dans AutorisationsTest.
 */
class MatriceRolesTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = [
        'tableau de bord' => '/admin',
        'organigramme' => '/admin/organigramme',
        'liste des agents' => '/admin/agents',
        'nouvel agent' => '/admin/agents/create',
        'liste des projets' => '/admin/projets',
        'nouveau projet' => '/admin/projets/create',
        'structures' => '/admin/structures',
        'utilisateurs' => '/admin/utilisateurs',
        'journal' => '/admin/journal',
        'notifications' => '/admin/notifications',
    ];

    /** Pages autorisées pour chaque compte ; toutes les autres doivent répondre 403. */
    public static function comptes(): array
    {
        $lecture = ['tableau de bord', 'organigramme', 'liste des agents', 'liste des projets', 'notifications'];
        $gestion = [...$lecture, 'nouvel agent', 'nouveau projet'];

        return [
            'administrateur' => ['admin', array_keys(self::PAGES)],
            'direction (DNH)' => ['dnh', [...$lecture, 'journal']],
            'direction (DNAH)' => ['dnah', [...$lecture, 'journal']],
            'chef de division' => ['chef.division', $gestion],
            'chef de section' => ['chef.section', $gestion],
            'chargé d\'études' => ['charge.etudes', $lecture],
            'consultation' => ['consultation', $lecture],
        ];
    }

    #[DataProvider('comptes')]
    public function test_acces_aux_pages_selon_le_role(string $compte, array $autorisees): void
    {
        $this->seed(DatabaseSeeder::class);
        $utilisateur = User::where('email', "{$compte}@dnh.local")->firstOrFail();

        foreach (self::PAGES as $nom => $url) {
            $attendu = in_array($nom, $autorisees, true) ? 200 : 403;
            $this->assertSame($attendu, $this->actingAs($utilisateur)->get($url)->status(), "{$compte} → {$nom} ({$url})");
        }
    }

    public function test_un_visiteur_ne_voit_que_la_partie_publique(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/')->assertOk();
        $this->get('/organigramme/donnees')->assertOk();
        $this->get('/organigramme.pdf')->assertOk();
        foreach (self::PAGES as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }
}
