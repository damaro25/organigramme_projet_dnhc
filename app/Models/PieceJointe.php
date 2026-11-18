<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

class PieceJointe extends Model
{
    protected $table = 'pieces_jointes';

    protected $fillable = ['projet_id', 'nom', 'chemin', 'taille', 'mime', 'ajoute_par'];

    protected function casts(): array
    {
        return ['taille' => 'integer'];
    }

    protected static function booted(): void
    {
        // Le fichier est supprimé du disque avec la pièce jointe.
        static::deleted(fn (self $p) => Storage::disk('public')->delete($p->chemin));
    }

    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ajoute_par');
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => Storage::disk('public')->url($this->chemin));
    }

    protected function tailleLisible(): Attribute
    {
        return Attribute::get(fn () => Number::fileSize($this->taille, precision: 1));
    }
}
