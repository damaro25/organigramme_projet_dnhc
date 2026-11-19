<?php

namespace App\Services;

use App\Enums\TypeStructure;
use App\Models\Agent;
use App\Models\Projet;
use App\Models\Structure;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Prépare les données des exports PDF (dompdf).
 * Les photos sont intégrées en JPEG base64 : dompdf n'a ainsi besoin d'aucun accès réseau.
 */
class ExportPdfService
{
    private ImageManager $images;

    /** @var array<string, string> cache des data URI par chemin */
    private array $photos = [];

    public function __construct()
    {
        $this->images = new ImageManager(new Driver);
    }

    /** Photo de l'agent en data URI JPEG, ou l'avatar par défaut. */
    /** Armoiries officielles si elles ont été déposées dans public/images, sinon l'emplacement provisoire. */
    public static function armoiries(): string
    {
        return public_path(\App\Support\Armoiries::image());
    }

    public function photo(?Agent $agent, bool $miniature = true, int $taille = 96): string
    {
        $chemin = $agent ? ($miniature ? ($agent->photo_miniature ?: $agent->photo) : ($agent->photo ?: $agent->photo_miniature)) : null;
        $cle = ($chemin ?? 'defaut').'@'.$taille;

        if (isset($this->photos[$cle])) {
            return $this->photos[$cle];
        }

        $disque = Storage::disk('public');
        $source = $chemin && $disque->exists($chemin) ? $disque->path($chemin) : public_path('images/avatar-defaut.png');

        try {
            $jpeg = (string) $this->images->read($source)->cover($taille, $taille)->toJpeg(85);
        } catch (\Throwable) {
            $jpeg = (string) file_get_contents(public_path('images/avatar-defaut.png'));

            return $this->photos[$cle] = 'data:image/png;base64,'.base64_encode($jpeg);
        }

        return $this->photos[$cle] = 'data:image/jpeg;base64,'.base64_encode($jpeg);
    }

    /** Données de l'organigramme complet, organisées pour une mise en page en tableaux. */
    public function organigramme(): array
    {
        $structures = Structure::query()->orderBy('ordre')->orderBy('nom')->get();
        $agents = Agent::query()->actifs()
            ->with(['projets' => fn ($q) => $q->nonArchives()->avecSommesPoids()])
            ->orderByDesc('est_responsable')->orderBy('ordre')->orderBy('nom')
            ->get()
            ->groupBy('structure_id');

        $enfants = $structures->groupBy('parent_id');
        $bloc = function (Structure $s) use ($agents, $enfants, &$bloc): array {
            $membres = $agents->get($s->id, collect());

            return [
                'structure' => $s,
                'responsable' => $membres->firstWhere('est_responsable', true),
                'agents' => $membres->where('est_responsable', false)->values(),
                'enfants' => $enfants->get($s->id, collect())->map($bloc)->values(),
            ];
        };

        $racine = $structures->firstWhere('parent_id', null);
        if (! $racine) {
            return ['racine' => null];
        }
        $arbre = $bloc($racine);

        $rattaches = $arbre['enfants']->filter(fn ($b) => $b['structure']->type === TypeStructure::ServiceRattache);
        $adjointe = $arbre['enfants']->first(fn ($b) => $b['structure']->type === TypeStructure::Direction);

        return [
            'racine' => $arbre,
            'appuiTechnique' => $rattaches->filter(fn ($b) => $b['structure']->groupe?->value !== 'personnel_appui')->values(),
            'personnelAppui' => $rattaches->filter(fn ($b) => $b['structure']->groupe?->value === 'personnel_appui')->values(),
            'adjointe' => $adjointe,
            'divisions' => $adjointe ? $adjointe['enfants']->filter(fn ($b) => $b['structure']->type === TypeStructure::Division)->values() : collect(),
            'deconcentres' => $adjointe ? $this->chaine($adjointe['enfants']->first(fn ($b) => $b['structure']->type === TypeStructure::ServiceDeconcentre)) : [],
        ];
    }

    /** Services déconcentrés : SD → I.R → D.P, aplatis en une chaîne. */
    private function chaine(?array $bloc): array
    {
        $chaine = [];
        while ($bloc) {
            $chaine[] = $bloc;
            $bloc = $bloc['enfants']->first();
        }

        return $chaine;
    }

    /** Projets d'un agent (et de sa structure s'il en est le chef). */
    public function ficheAgent(Agent $agent): array
    {
        $agent->load(['structure.parent', 'projets' => fn ($q) => $q->nonArchives()->with('etapes', 'structure', 'responsables')->orderBy('date_fin_prevue')]);

        $structure = null;
        if ($agent->estChefDeStructure()) {
            $projets = $agent->structure->projetsConsolides()->with(['etapes', 'structure', 'responsables'])->orderBy('date_fin_prevue')->get();
            $structure = ['nom' => $agent->structure->nom, 'taux' => Projet::moyenneTaux($projets), 'projets' => $projets];
        }

        return [
            'agent' => $agent,
            'photo' => $this->photo($agent, false, 200),
            'taux' => Projet::moyenneTaux($agent->projets),
            'projets' => $agent->projets,
            'structure' => $structure,
        ];
    }

    /** Couleur hexadécimale de la barre de progression (dompdf ne connaît pas les classes Tailwind). */
    public static function couleurHex(?int $taux): string
    {
        return match (Projet::couleurPourTaux($taux)) {
            'rouge' => '#dc2626',
            'orange' => '#ea580c',
            'vert' => '#16a34a',
            default => '#94a3b8',
        };
    }

    /** Une couleur claire (fond des services d'appui technique) est lisible en texte foncé. */
    public static function estClaire(string $hex): bool
    {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 186;
    }
}
