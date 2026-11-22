<?php

namespace App\Services;

use App\Enums\GroupeStructure;
use App\Enums\StatutProjet;
use App\Enums\TypeLien;
use App\Enums\TypeStructure;
use App\Models\Agent;
use App\Models\Etape;
use App\Models\Projet;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

/**
 * Met en forme les entrées du journal (spatie/activitylog) pour l'affichage :
 * libellés français des champs, valeurs lisibles (dates, oui/non, noms au lieu des identifiants).
 */
class JournalService
{
    public const TYPES = [
        'agents' => 'Agents et photos',
        'projets' => 'Projets',
        'etapes' => 'Étapes',
        'structures' => 'Structures',
        'utilisateurs' => 'Comptes utilisateurs',
    ];

    public const SUJETS = [
        Agent::class => 'Agent',
        Projet::class => 'Projet',
        Etape::class => 'Étape',
        Structure::class => 'Structure',
        User::class => 'Compte',
    ];

    public const CHAMPS = [
        'nom' => 'Nom', 'prenom' => 'Prénom', 'poste' => 'Poste', 'email' => 'E-mail', 'telephone' => 'Téléphone',
        'structure_id' => 'Structure', 'superieur_id' => 'Supérieur', 'est_responsable' => 'Titulaire de la structure',
        'actif' => 'Actif', 'ordre' => 'Ordre', 'photo' => 'Photo', 'photo_miniature' => 'Miniature',
        'titre' => 'Titre', 'description' => 'Description', 'date_debut' => 'Date de début', 'date_fin_prevue' => 'Date de fin prévue',
        'statut' => 'Statut', 'archive' => 'Archivé', 'valide_par' => 'Validé par', 'valide_le' => 'Validé le',
        'affectations' => 'Agents affectés', 'pieces_jointes' => 'Fichiers',
        'projet_id' => 'Projet', 'libelle' => 'Libellé', 'poids' => 'Poids', 'est_terminee' => 'Terminée',
        'date_realisation' => 'Date de réalisation', 'commentaire' => 'Commentaire',
        'sigle' => 'Sigle', 'type' => 'Type', 'groupe' => 'Groupe', 'parent_id' => 'Structure parente',
        'type_lien' => 'Lien', 'couleur' => 'Couleur', 'name' => 'Nom', 'agent_id' => 'Fiche agent',
    ];

    /** Champs techniques masqués (la miniature suit toujours la photo). */
    public const PHOTO_SUPPRIMEE = '#photo-supprimee';

    private const MASQUES = ['photo_miniature'];

    private const DATES = ['date_debut', 'date_fin_prevue', 'date_realisation', 'valide_le'];

    private const BOOLEENS = ['est_responsable', 'actif', 'archive', 'est_terminee'];

    /** @var array<string, array<int, string>> cache des noms par type de clé étrangère */
    private array $noms = [];

    /**
     * Liste des changements d'une entrée : [['champ' => libellé, 'ancien' => …, 'nouveau' => …, 'photo' => bool]].
     * Les valeurs sont déjà mises en forme (chaînes, ou URL pour une photo).
     */
    public function changements(Activity $activite): Collection
    {
        $nouveaux = (array) ($activite->properties['attributes'] ?? []);
        $anciens = (array) ($activite->properties['old'] ?? []);

        return collect(array_unique([...array_keys($anciens), ...array_keys($nouveaux)]))
            ->reject(fn ($champ) => in_array($champ, self::MASQUES, true))
            ->map(fn ($champ) => [
                'champ' => self::CHAMPS[$champ] ?? $champ,
                'photo' => $champ === 'photo',
                'ancien' => array_key_exists($champ, $anciens) ? $this->valeur($champ, $anciens[$champ]) : null,
                'nouveau' => array_key_exists($champ, $nouveaux) ? $this->valeur($champ, $nouveaux[$champ]) : null,
                'creation' => ! array_key_exists($champ, $anciens),
            ])
            ->values();
    }

    /** Intitulé de l'objet concerné, même s'il a été supprimé depuis. */
    public function sujet(Activity $activite): string
    {
        $type = self::SUJETS[$activite->subject_type] ?? class_basename((string) $activite->subject_type);
        $s = $activite->subject;

        $nom = match (true) {
            $s instanceof Agent => $s->nom_complet,
            $s instanceof Projet => $s->titre,
            $s instanceof Etape => $s->libelle.($s->projet ? ' — '.$s->projet->titre : ''),
            $s instanceof Structure => $s->nom,
            $s instanceof User => $s->name,
            default => '#'.$activite->subject_id.' (supprimé)',
        };

        return $type.' : '.$nom;
    }

    public function valeur(string $champ, mixed $valeur): ?string
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }

        return match (true) {
            // L'ancienne photo est supprimée du disque lors d'un remplacement.
            $champ === 'photo' => Storage::disk('public')->exists($valeur) ? Storage::disk('public')->url($valeur) : self::PHOTO_SUPPRIMEE,
            in_array($champ, self::BOOLEENS, true) => $valeur ? 'Oui' : 'Non',
            in_array($champ, self::DATES, true) => rescue(fn () => Carbon::parse($valeur)->format('d/m/Y'), (string) $valeur, false),
            $champ === 'statut' => StatutProjet::tryFrom($valeur)?->libelle() ?? $valeur,
            $champ === 'type' => TypeStructure::tryFrom($valeur)?->libelle() ?? $valeur,
            $champ === 'groupe' => GroupeStructure::tryFrom($valeur)?->libelle() ?? $valeur,
            $champ === 'type_lien' => TypeLien::tryFrom($valeur)?->libelle() ?? $valeur,
            in_array($champ, ['structure_id', 'parent_id'], true) => $this->nom('structure', $valeur),
            in_array($champ, ['superieur_id', 'agent_id'], true) => $this->nom('agent', $valeur),
            $champ === 'valide_par' => $this->nom('user', $valeur),
            $champ === 'projet_id' => $this->nom('projet', $valeur),
            is_array($valeur) => json_encode($valeur, JSON_UNESCAPED_UNICODE),
            default => (string) $valeur,
        };
    }

    private function nom(string $type, mixed $id): string
    {
        $this->noms[$type] ??= match ($type) {
            'structure' => Structure::query()->pluck('nom', 'id')->all(),
            'agent' => Agent::query()->get(['id', 'nom', 'prenom'])->mapWithKeys(fn ($a) => [$a->id => $a->nom_complet])->all(),
            'user' => User::query()->pluck('name', 'id')->all(),
            'projet' => Projet::query()->pluck('titre', 'id')->all(),
        };

        return $this->noms[$type][$id] ?? '#'.$id;
    }
}
