<?php

namespace App\Models;

use App\Enums\GroupeStructure;
use App\Enums\TypeLien;
use App\Enums\TypeStructure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Structure extends Model
{
    use HasFactory, LogsActivity;

    /** Couleur par défaut quand ni la structure ni sa division n'en ont. */
    public const COULEUR_DEFAUT = '#1d4ed8';

    protected $fillable = [
        'nom', 'sigle', 'type', 'groupe', 'parent_id', 'type_lien', 'couleur', 'ordre',
    ];

    protected function casts(): array
    {
        return [
            'type' => TypeStructure::class,
            'groupe' => GroupeStructure::class,
            'type_lien' => TypeLien::class,
            'ordre' => 'integer',
        ];
    }

    /** Carte parent_id => [ids enfants], mise en cache pour la durée de la requête. */
    protected static ?Collection $carteEnfants = null;

    protected static function booted(): void
    {
        $reset = fn () => static::$carteEnfants = null;
        static::saved($reset);
        static::deleted($reset);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('structures')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $e) => "Structure {$this->libelleEvenement($e)}");
    }

    /* ---------------- Relations ---------------- */

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function enfants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('ordre');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class)->orderBy('ordre');
    }

    public function responsable(): HasOne
    {
        return $this->hasOne(Agent::class)->where('est_responsable', true)->where('actif', true);
    }

    public function projets(): HasMany
    {
        return $this->hasMany(Projet::class);
    }

    /* ---------------- Scopes ---------------- */

    public function scopeOrdonne(Builder $q): Builder
    {
        return $q->orderBy('ordre')->orderBy('nom');
    }

    public function scopeDeType(Builder $q, TypeStructure $type): Builder
    {
        return $q->where('type', $type->value);
    }

    /* ---------------- Arborescence ---------------- */

    /** Identifiants de la structure et de toutes ses sous-structures. */
    public function descendantIds(bool $inclureSoi = true): array
    {
        static::$carteEnfants ??= static::query()->get(['id', 'parent_id'])->groupBy('parent_id')
            ->map(fn ($g) => $g->pluck('id')->all());

        $ids = $inclureSoi ? [$this->id] : [];
        $pile = [$this->id];
        while ($pile) {
            $courant = array_pop($pile);
            foreach (static::$carteEnfants->get($courant, []) as $enfant) {
                $ids[] = $enfant;
                $pile[] = $enfant;
            }
        }

        return $ids;
    }

    /** Division à laquelle appartient la structure (elle-même si c'est une division). */
    public function division(): ?self
    {
        $s = $this;
        while ($s) {
            if ($s->type === TypeStructure::Division) {
                return $s;
            }
            $s = $s->parent;
        }

        return null;
    }

    /** Couleur propre, sinon celle de la division, sinon la couleur par défaut. */
    protected function couleurEffective(): Attribute
    {
        return Attribute::get(fn () => $this->couleur
            ?? $this->division()?->couleur
            ?? self::COULEUR_DEFAUT);
    }

    protected function libelleCourt(): Attribute
    {
        return Attribute::get(fn () => $this->sigle ?: $this->nom);
    }

    /* ---------------- Indicateurs ---------------- */

    /** Projets non archivés de la structure et de ses sous-structures. */
    public function projetsConsolides(): Builder
    {
        return Projet::query()->nonArchives()->whereIn('structure_id', $this->descendantIds());
    }

    /** Taux consolidé : moyenne simple des taux des projets non archivés de la structure et de ses sous-structures. */
    public function tauxConsolide(): ?int
    {
        return Projet::moyenneTaux($this->projetsConsolides()->avecSommesPoids()->get());
    }

    private function libelleEvenement(string $e): string
    {
        return ['created' => 'créée', 'updated' => 'modifiée', 'deleted' => 'supprimée'][$e] ?? $e;
    }
}
