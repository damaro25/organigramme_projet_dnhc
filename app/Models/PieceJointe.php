<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

class PieceJointe extends Model
{
    /** Types acceptés pour les justificatifs d'étape : documents bureautiques et photos. */
    public const EXTENSIONS_ETAPE = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'jpg', 'jpeg', 'png', 'webp'];

    protected $table = 'pieces_jointes';

    protected $fillable = ['projet_id', 'etape_id', 'nom', 'chemin', 'taille', 'mime', 'ajoute_par'];

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

    protected function tailleLisible(): Attribute
    {
        return Attribute::get(fn () => Number::fileSize($this->taille, precision: 1));
    }

    protected function estImage(): Attribute
    {
        return Attribute::get(fn () => str_starts_with((string) $this->mime, 'image/'));
    }

    protected function estPdf(): Attribute
    {
        return Attribute::get(fn () => $this->mime === 'application/pdf');
    }

    /** Le navigateur sait afficher le fichier sans le télécharger (PDF, images). */
    protected function apercuPossible(): Attribute
    {
        return Attribute::get(fn () => $this->est_image || $this->est_pdf);
    }
}
