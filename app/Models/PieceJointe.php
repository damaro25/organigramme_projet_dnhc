<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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

    /** Taille lisible (« 1,4 Mo »), sans dépendre de l'extension PHP intl. */
    protected function tailleLisible(): Attribute
    {
        return Attribute::get(fn () => self::octetsLisibles((int) $this->taille));
    }

    public static function octetsLisibles(int $octets): string
    {
        $unites = ['o', 'Ko', 'Mo', 'Go'];
        $i = 0;
        $valeur = (float) $octets;
        while ($valeur >= 1024 && $i < count($unites) - 1) {
            $valeur /= 1024;
            $i++;
        }

        return ($i === 0 ? (string) $octets : number_format($valeur, 1, ',', ' ')).' '.$unites[$i];
    }

    /** pdf, image ou autre : ce que la visionneuse sait afficher. */
    protected function typeApercu(): Attribute
    {
        return Attribute::get(fn () => match (true) {
            $this->mime === 'application/pdf' => 'pdf',
            $this->est_visualisable && $this->est_image => 'image',
            default => 'autre',
        });
    }

    /** Données passées à la visionneuse (événement « voir-fichier »). */
    public function pourVisionneuse(): array
    {
        return [
            'nom' => $this->nom,
            'taille' => $this->taille_lisible,
            'type' => $this->type_apercu,
            'apercu' => route('admin.projets.pieces-jointes.apercu', [$this->projet_id, $this->id]),
            'telechargement' => route('admin.projets.pieces-jointes.show', [$this->projet_id, $this->id]),
        ];
    }
}
