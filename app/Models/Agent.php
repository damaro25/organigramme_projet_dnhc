<?php

namespace App\Models;

use App\Enums\RoleProjet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Agent extends Model
{
    use HasFactory, LogsActivity;

    public const AVATAR_DEFAUT = 'images/avatar-defaut.svg';

    protected $fillable = [
        'nom', 'prenom', 'poste', 'structure_id', 'superieur_id', 'email', 'telephone',
        'photo', 'photo_miniature', 'est_responsable', 'actif', 'ordre',
    ];

    protected $appends = ['nom_complet', 'photo_url', 'miniature_url'];

    protected function casts(): array
    {
        return [
            'est_responsable' => 'boolean',
            'actif' => 'boolean',
            'ordre' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('agents')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $e) => match ($e) {
                'created' => 'Agent créé',
                'updated' => $this->wasChanged('photo') ? 'Photo de l\'agent modifiée' : 'Agent modifié',
                'deleted' => 'Agent supprimé',
                default => $e,
            });
    }

    /* ---------------- Relations ---------------- */

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function superieur(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superieur_id');
    }

    public function subordonnes(): HasMany
    {
        return $this->hasMany(self::class, 'superieur_id')->orderBy('ordre');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function projets(): BelongsToMany
    {
        return $this->belongsToMany(Projet::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function projetsResponsable(): BelongsToMany
    {
        return $this->projets()->wherePivot('role', RoleProjet::Responsable->value);
    }

    /* ---------------- Scopes ---------------- */

    public function scopeActifs(Builder $q): Builder
    {
        return $q->where('actif', true);
    }

    public function scopeRecherche(Builder $q, ?string $terme): Builder
    {
        if (! filled($terme)) {
            return $q;
        }

        return $q->where(fn ($w) => $w
            ->where('nom', 'like', "%{$terme}%")
            ->orWhere('prenom', 'like', "%{$terme}%")
            ->orWhere('poste', 'like', "%{$terme}%"));
    }

    /** Agents rattachés à une structure ou à l'une de ses sous-structures. */
    public function scopeDansStructure(Builder $q, Structure|int|null $structure): Builder
    {
        if (! $structure) {
            return $q;
        }
        $structure = $structure instanceof Structure ? $structure : Structure::findOrFail($structure);

        return $q->whereIn('structure_id', $structure->descendantIds());
    }

    /* ---------------- Accesseurs ---------------- */

    protected function nomComplet(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->prenom} {$this->nom}"));
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo
            ? Storage::disk('public')->url($this->photo)
            : asset(self::AVATAR_DEFAUT));
    }

    protected function miniatureUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo_miniature
            ? Storage::disk('public')->url($this->photo_miniature)
            : $this->photo_url);
    }

    /** Taux moyen d'exécution des projets non archivés de l'agent (null s'il n'en a aucun). */
    protected function tauxMoyen(): Attribute
    {
        return Attribute::get(function () {
            $projets = $this->relationLoaded('projets')
                ? $this->projets->where('archive', false)
                : $this->projets()->nonArchives()->avecSommesPoids()->get();

            return Projet::moyenneTaux($projets);
        });
    }

    /** Vrai si l'agent dirige une division ou une section (le panneau affiche alors les projets de sa structure). */
    public function estChefDeStructure(): bool
    {
        return $this->est_responsable
            && in_array($this->structure?->type?->value, ['division', 'section', 'direction'], true);
    }
}
