<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Etape;
use App\Models\Projet;
use App\Models\Structure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Règles de calcul du taux d'exécution, des retards et des taux consolidés. */
class TauxExecutionTest extends TestCase
{
    use RefreshDatabase;

    /** Crée un projet dont les étapes ont les poids donnés ; « true » = étape terminée. */
    private function projet(array $etapes, array $attributs = []): Projet
    {
        $projet = Projet::factory()->create($attributs);
        foreach ($etapes as $i => [$poids, $terminee]) {
            Etape::factory()->for($projet)->create(['poids' => $poids, 'est_terminee' => $terminee, 'ordre' => $i + 1]);
        }

        return $projet;
    }

    /** Le taux doit être identique quelle que soit la façon de le charger. */
    private function assertTaux(int $attendu, Projet $projet): void
    {
        $this->assertSame($attendu, $projet->fresh()->taux_execution, 'requête directe');
        $this->assertSame($attendu, $projet->fresh()->load('etapes')->taux_execution, 'étapes chargées');
        $this->assertSame($attendu, Projet::avecSommesPoids()->find($projet->id)->taux_execution, 'sommes SQL');
    }

    public function test_un_projet_sans_etape_est_a_zero(): void
    {
        $this->assertTaux(0, $this->projet([]));
    }

    public function test_le_taux_est_le_poids_termine_sur_le_poids_total(): void
    {
        // 2 + 1 terminés sur 2 + 1 + 3 + 4 = 3 / 10
        $this->assertTaux(30, $this->projet([[2, true], [1, true], [3, false], [4, false]]));
    }

    public function test_les_poids_comptent_et_pas_le_nombre_d_etapes(): void
    {
        // 1 étape sur 2 terminée, mais elle pèse 3 sur 4 : 75 %, pas 50 %.
        $this->assertTaux(75, $this->projet([[3, true], [1, false]]));
    }

    public function test_le_taux_est_arrondi_a_l_entier_le_plus_proche(): void
    {
        $this->assertTaux(33, $this->projet([[1, true], [1, false], [1, false]]));
        $this->assertTaux(67, $this->projet([[1, true], [1, true], [1, false]]));
    }

    public function test_toutes_les_etapes_terminees_donnent_cent(): void
    {
        $this->assertTaux(100, $this->projet([[2, true], [5, true]]));
    }

    public function test_cocher_puis_decocher_recalcule_le_taux(): void
    {
        $projet = $this->projet([[1, false], [1, false]]);
        $etape = $projet->etapes()->first();

        $etape->cocher();
        $this->assertTaux(50, $projet);

        $etape->decocher();
        $this->assertTaux(0, $projet);
    }

    public function test_un_projet_est_en_retard_si_l_echeance_est_passee_et_le_taux_inferieur_a_cent(): void
    {
        $passe = ['date_debut' => now()->subMonths(3), 'date_fin_prevue' => now()->subDay()];

        $this->assertTrue($this->projet([[1, true], [1, false]], $passe)->fresh()->est_en_retard);
        $this->assertFalse($this->projet([[1, true]], $passe)->fresh()->est_en_retard, 'terminé à 100 %');
        $this->assertFalse($this->projet([[1, false]], ['date_debut' => now()->subMonth(), 'date_fin_prevue' => today()])->fresh()->est_en_retard, 'échéance aujourd’hui');
        $this->assertFalse($this->projet([[1, false]], ['date_fin_prevue' => null])->fresh()->est_en_retard, 'sans échéance');
    }

    public function test_la_couleur_suit_les_seuils_30_et_70(): void
    {
        $this->assertSame('rouge', Projet::couleurPourTaux(29));
        $this->assertSame('orange', Projet::couleurPourTaux(30));
        $this->assertSame('orange', Projet::couleurPourTaux(70));
        $this->assertSame('vert', Projet::couleurPourTaux(71));
        $this->assertSame('gris', Projet::couleurPourTaux(null));
    }

    public function test_le_taux_moyen_d_un_agent_ignore_les_projets_archives(): void
    {
        $agent = Agent::factory()->create();
        $a = $this->projet([[1, true], [1, false]]);          // 50 %
        $b = $this->projet([[1, true]]);                      // 100 %
        $archive = $this->projet([[1, false]], ['archive' => true]); // 0 %, ignoré
        $agent->projets()->attach([$a->id => ['role' => 'membre'], $b->id => ['role' => 'membre'], $archive->id => ['role' => 'membre']]);

        $this->assertSame(75, $agent->fresh()->taux_moyen);
        $this->assertNull(Agent::factory()->create()->taux_moyen, 'agent sans projet');
    }

    public function test_le_taux_consolide_d_une_division_inclut_ses_sections(): void
    {
        $division = Structure::factory()->division()->create();
        $section1 = Structure::factory()->create(['parent_id' => $division->id]);
        $section2 = Structure::factory()->create(['parent_id' => $division->id]);
        $autre = Structure::factory()->create();

        $this->projet([[1, true]], ['structure_id' => $division->id]);                  // 100 %
        $this->projet([[1, true], [1, false]], ['structure_id' => $section1->id]);    // 50 %
        $this->projet([[1, false]], ['structure_id' => $section2->id]);                // 0 %
        $this->projet([[1, true]], ['structure_id' => $autre->id]);                    // hors périmètre
        $this->projet([[1, false]], ['structure_id' => $section1->id, 'archive' => true]); // archivé

        $this->assertSame(50, $division->tauxConsolide());   // (100 + 50 + 0) / 3
        $this->assertSame(50, $section1->tauxConsolide());
        $this->assertNull(Structure::factory()->create()->tauxConsolide());
    }
}
