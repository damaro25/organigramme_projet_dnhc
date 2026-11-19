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

    protected $fillable = ['projet_id', 'etape_id', 'nom', 'chemin', 'taille', 'mime', 'ajoute_par'];

    /** Types que le navigateur sait afficher directement (aperçu). */
    public const TYPES_APERCU = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /** Extensions acceptées pour les pièces jointes. */
    public const EXTENSIONS = 'pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,ppt,pptx,odt,ods';

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

    public function etape(): BelongsTo
    {
        return $this->belongsTo(Etape::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ajoute_par');
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => Storage::disk('public')->url($this->chemin));
    }

    /** Vrai si le fichier peut s'ouvrir dans le navigateur (PDF ou image). */
    protected function estVisualisable(): Attribute
    {
        return Attribute::get(fn () => in_array($this->mime, self::TYPES_APERCU, true));
    }

    protected function estImage(): Attribute
    {
        return Attribute::get(fn () => str_starts_with((string) $this->mime, 'image/'));
    }

    protected function tailleLisible(): Attribute
    {
        return Attribute::get(fn () => Number::fileSize($this->taille, precision: 1));
    }
}
