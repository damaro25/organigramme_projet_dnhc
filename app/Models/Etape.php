<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Etape extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'projet_id', 'libelle', 'ordre', 'poids', 'est_terminee',
        'date_realisation', 'commentaire', 'valide_par',
    ];

    protected function casts(): array
    {
        return [
            'ordre' => 'integer',
            'poids' => 'integer',
            'est_terminee' => 'boolean',
            'date_realisation' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $etape) {
            $etape->ordre ??= (int) static::where('projet_id', $etape->projet_id)->max('ordre') + 1;
        });

        // Les fichiers joints sont supprimés du disque avec l'étape (la clé étrangère
        // supprime les lignes, mais pas les fichiers).
        static::deleting(fn (self $etape) => $etape->piecesJointes->each->delete());

        // Cohérence : une étape terminée a toujours une date, une étape décochée n'en a plus.
        static::saving(function (self $etape) {
            if ($etape->est_terminee) {
                $etape->date_realisation ??= today();
            } else {
                $etape->date_realisation = null;
                $etape->valide_par = null;
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('etapes')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $e) => match (true) {
                $e === 'updated' && $this->wasChanged('est_terminee') => $this->est_terminee ? 'Étape cochée' : 'Étape décochée',
                $e === 'created' => 'Étape ajoutée',
                $e === 'updated' => 'Étape modifiée',
                $e === 'deleted' => 'Étape supprimée',
                default => $e,
            });
    }

    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }

    /** Justificatifs joints lors de la validation de l'étape. */
    public function piecesJointes(): HasMany
    {
        return $this->hasMany(PieceJointe::class)->latest('id');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    /** Marque l'étape comme terminée. */
    public function cocher(?User $par = null, $date = null, ?string $commentaire = null): self
    {
        $this->fill([
            'est_terminee' => true,
            'date_realisation' => $date ?? today(),
            'commentaire' => $commentaire ?? $this->commentaire,
            'valide_par' => $par?->id,
        ])->save();

        return $this;
    }

    public function decocher(): self
    {
        $this->update(['est_terminee' => false]);

        return $this;
    }
}
