<?php

namespace App\Models;

use App\Enums\RoleProjet;
use App\Enums\StatutProjet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Projet extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'titre', 'description', 'structure_id', 'date_debut', 'date_fin_prevue',
        'statut', 'archive', 'valide_par', 'valide_le',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin_prevue' => 'date',
            'statut' => StatutProjet::class,
            'archive' => 'boolean',
            'valide_le' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('projets')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $e) => match ($e) {
                'created' => 'Projet créé',
                'updated' => 'Projet modifié',
                'deleted' => 'Projet supprimé',
                default => $e,
            });
    }

    /* ---------------- Relations ---------------- */

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class)->withPivot('role')->withTimestamps();
    }

    public function responsables(): BelongsToMany
    {
        return $this->agents()->wherePivot('role', RoleProjet::Responsable->value);
    }

    public function membres(): BelongsToMany
    {
        return $this->agents()->wherePivot('role', RoleProjet::Membre->value);
    }

    public function etapes(): HasMany
    {
        return $this->hasMany(Etape::class)->orderBy('ordre');
    }

    /** Toutes les pièces jointes : documents du projet et justificatifs des étapes. */
    public function piecesJointes(): HasMany
    {
        return $this->hasMany(PieceJointe::class);
    }

    /** Documents du projet lui-même, hors justificatifs d'étapes. */
    public function documents(): HasMany
    {
        return $this->hasMany(PieceJointe::class)->whereNull('etape_id');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    /* ---------------- Scopes ---------------- */

    public function scopeNonArchives(Builder $q): Builder
    {
        return $q->where('archive', false);
    }

    /** Ajoute poids_total et poids_termine pour calculer le taux sans charger les étapes. */
    public function scopeAvecSommesPoids(Builder $q): Builder
    {
        return $q->withSum('etapes as poids_total', 'poids')
            ->withSum(['etapes as poids_termine' => fn ($e) => $e->where('est_terminee', true)], 'poids');
    }

    /**
     * Projets en retard : échéance dépassée et au moins une étape non terminée
     * (équivaut à un taux inférieur à 100 %).
     */
    public function scopeEnRetard(Builder $q): Builder
    {
        return $q->whereNotNull('date_fin_prevue')
            ->whereDate('date_fin_prevue', '<', now()->toDateString())
            ->where(fn ($w) => $w
                ->whereDoesntHave('etapes')
                ->orWhereHas('etapes', fn ($e) => $e->where('est_terminee', false)));
    }

    public function scopeFiltrer(Builder $q, array $f): Builder
    {
        return $q
            ->when($f['statut'] ?? null, fn ($w, $v) => $w->where('statut', $v))
            ->when($f['structure_id'] ?? null, fn ($w, $v) => $w->whereIn('structure_id', Structure::findOrFail($v)->descendantIds()))
            ->when($f['agent_id'] ?? null, fn ($w, $v) => $w->whereHas('agents', fn ($a) => $a->where('agents.id', $v)))
            ->when($f['recherche'] ?? null, fn ($w, $v) => $w->where('titre', 'like', "%{$v}%"))
            ->when(isset($f['archive']), fn ($w) => $w->where('archive', (bool) $f['archive']));
    }

    /* ---------------- Accesseurs ---------------- */

    /**
     * Taux d'exécution (0 à 100) :
     * (somme des poids des étapes terminées ÷ somme totale des poids) × 100.
     * Un projet sans étape vaut 0 %.
     */
    protected function tauxExecution(): Attribute
    {
        return Attribute::get(function () {
            [$total, $termine] = $this->sommesPoids();

            return $total > 0 ? (int) round($termine / $total * 100) : 0;
        });
    }

    /**
     * En retard : échéance dépassée et au moins une étape non terminée (ou aucune étape).
     * La comparaison se fait sur les poids exacts et non sur le taux arrondi : un projet
     * à 99,6 % s'affiche « 100 % » mais reste en retard, comme dans scopeEnRetard().
     */
    protected function estEnRetard(): Attribute
    {
        return Attribute::get(function () {
            if ($this->date_fin_prevue === null || ! $this->date_fin_prevue->lt(today())) {
                return false;
            }
            [$total, $termine] = $this->sommesPoids();

            return $total === 0 || $termine < $total;
        });
    }

    /** @return array{0: int, 1: int} [somme des poids, somme des poids des étapes terminées] */
    private function sommesPoids(): array
    {
        if (array_key_exists('poids_total', $this->attributes)) {
            return [(int) $this->attributes['poids_total'], (int) ($this->attributes['poids_termine'] ?? 0)];
        }
        if ($this->relationLoaded('etapes')) {
            return [(int) $this->etapes->sum('poids'), (int) $this->etapes->where('est_terminee', true)->sum('poids')];
        }
        $sommes = $this->etapes()->toBase()->selectRaw(
            'COALESCE(SUM(poids),0) AS total, COALESCE(SUM(CASE WHEN est_terminee = 1 THEN poids ELSE 0 END),0) AS termine'
        )->first();

        return [(int) $sommes->total, (int) $sommes->termine];
    }

    /** Couleur de la barre de progression : rouge < 30 %, orange 30–70 %, vert > 70 %. */
    protected function couleurTaux(): Attribute
    {
        return Attribute::get(fn () => self::couleurPourTaux($this->taux_execution));
    }

    public static function couleurPourTaux(?int $taux): string
    {
        return match (true) {
            $taux === null => 'gris',
            $taux < 30 => 'rouge',
            $taux <= 70 => 'orange',
            default => 'vert',
        };
    }

    /** Moyenne simple des taux d'une collection de projets (null si vide). */
    public static function moyenneTaux(iterable $projets): ?int
    {
        $taux = collect($projets)->map(fn (self $p) => $p->taux_execution);

        return $taux->isEmpty() ? null : (int) round($taux->avg());
    }

    /* ---------------- Actions ---------------- */

    /** Duplique le projet (étapes remises à zéro, sans pièces jointes) pour s'en servir comme modèle. */
    public function dupliquer(): self
    {
        return DB::transaction(function () {
            $copie = $this->replicate(['valide_par', 'valide_le', 'poids_total', 'poids_termine']);
            $copie->unsetRelations();
            $copie->titre = $this->titre.' (copie)';
            $copie->statut = StatutProjet::NonDemarre;
            $copie->archive = false;
            $copie->save();

            foreach ($this->etapes as $etape) {
                $copie->etapes()->create($etape->only(['libelle', 'ordre', 'poids']));
            }
            $copie->agents()->sync(
                $this->agents->mapWithKeys(fn ($a) => [$a->id => ['role' => $a->pivot->role]])->all()
            );

            return $copie->fresh(['etapes', 'agents']);
        });
    }

    /** Réordonne les étapes selon la liste d'identifiants reçue (glisser-déposer). */
    public function reordonnerEtapes(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $i => $id) {
                $this->etapes()->whereKey($id)->update(['ordre' => $i + 1]);
            }
        });
    }
}
