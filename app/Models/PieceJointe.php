<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fichier joint à un projet (documents du projet, etape_id vide) ou à l'une de
 * ses étapes (justificatifs joints lors de la validation de l'étape).
 */
class PieceJointe extends Model
{
    protected $table = 'pieces_jointes';

    /** Formats acceptés : documents bureautiques, PDF et photos. */
    public const EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'jpg', 'jpeg', 'png', 'webp'];

    /** Formats affichés directement dans la visionneuse (les autres se téléchargent). */
    public const EXTENSIONS_VISUALISABLES = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

    /** Taille maximale d'un fichier, en kilo-octets (10 Mo). */
    public const TAILLE_MAX_KO = 10240;

    /** Nombre maximal de fichiers envoyés en une fois. */
    public const NOMBRE_MAX = 10;

    protected $fillable = ['projet_id', 'etape_id', 'nom', 'chemin', 'taille', 'mime', 'ajoute_par'];

    protected function casts(): array
    {
        return ['taille' => 'integer', 'etape_id' => 'integer', 'ajoute_par' => 'integer'];
    }

    protected static function booted(): void
    {
        // Le fichier est supprimé du disque avec la pièce jointe.
        static::deleted(fn (self $p) => Storage::disk('public')->delete($p->chemin));
    }

    /* ---------------- Validation partagée ---------------- */

    /** Règles d'un fichier envoyé (formulaire du projet et validation d'étape). */
    public static function reglesFichier(): array
    {
        return ['file', 'mimes:'.implode(',', self::EXTENSIONS), 'max:'.self::TAILLE_MAX_KO];
    }

    /**
     * Taille maximale réellement acceptée, en octets : la plus petite entre la limite de
     * l'application et celles de PHP (upload_max_filesize, post_max_size). Au-delà, PHP
     * rejette le fichier avant même Laravel ; on l'affiche pour prévenir l'utilisateur.
     */
    public static function tailleMaxEffective(): int
    {
        $enOctets = function (string|false $valeur): int {
            $valeur = trim((string) $valeur);
            if ($valeur === '' || $valeur === '-1' || $valeur === '0') {
                return PHP_INT_MAX;
            }
            $nombre = (float) $valeur;

            return (int) match (strtolower(substr($valeur, -1))) {
                'g' => $nombre * 1024 ** 3,
                'm' => $nombre * 1024 ** 2,
                'k' => $nombre * 1024,
                default => $nombre,
            };
        };

        return min(self::TAILLE_MAX_KO * 1024, $enOctets(ini_get('upload_max_filesize')), $enOctets(ini_get('post_max_size')));
    }

    public static function tailleMaxLisible(): string
    {
        return self::octetsLisibles(self::tailleMaxEffective());
    }

    /** Taille en français : « 850 Ko », « 2,4 Mo » (sans dépendre de l'extension PHP intl). */
    public static function octetsLisibles(int $octets): string
    {
        $decimal = fn (float $v) => str_replace('.', ',', (string) round($v, 1));

        return match (true) {
            $octets >= 1024 ** 3 => $decimal($octets / 1024 ** 3).' Go',
            $octets >= 1024 ** 2 => $decimal($octets / 1024 ** 2).' Mo',
            default => max(1, (int) round($octets / 1024)).' Ko',
        };
    }

    /** Valeur de l'attribut accept des champs de fichier. */
    public static function accept(): string
    {
        return implode(',', array_map(fn ($e) => '.'.$e, self::EXTENSIONS));
    }

    /** Enregistre un fichier envoyé sur le disque et crée la pièce jointe. */
    public static function depuisFichier(UploadedFile $fichier, Projet $projet, ?Etape $etape, ?User $auteur): self
    {
        $dossier = $etape ? "projets/{$projet->id}/etapes/{$etape->id}" : "projets/{$projet->id}";

        return self::create([
            'projet_id' => $projet->id,
            'etape_id' => $etape?->id,
            'nom' => Str::limit($fichier->getClientOriginalName(), 250, ''),
            'chemin' => $fichier->store($dossier, 'public'),
            'taille' => $fichier->getSize(),
            'mime' => $fichier->getMimeType(),
            'ajoute_par' => $auteur?->id,
        ]);
    }

    /**
     * Trace l'ajout ou la suppression de fichiers dans le journal des modifications,
     * sur le projet (log « projets ») ou sur l'étape (log « etapes »).
     */
    public static function journaliser(Projet|Etape $sujet, string $description, array $noms, ?User $auteur): void
    {
        if ($noms === []) {
            return;
        }

        activity($sujet instanceof Etape ? 'etapes' : 'projets')
            ->performedOn($sujet)
            ->causedBy($auteur)
            ->withProperties(['attributes' => ['pieces_jointes' => implode(', ', $noms)]])
            ->log($description);
    }

    /* ---------------- Relations et portées ---------------- */

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

    /** Documents du projet lui-même (hors justificatifs d'étapes). */
    public function scopeDuProjet(Builder $query): Builder
    {
        return $query->whereNull('etape_id');
    }

    /* ---------------- Accesseurs ---------------- */

    protected function url(): Attribute
    {
        return Attribute::get(fn () => Storage::disk('public')->url($this->chemin));
    }

    protected function tailleLisible(): Attribute
    {
        return Attribute::get(fn () => self::octetsLisibles($this->taille));
    }

    protected function extension(): Attribute
    {
        return Attribute::get(fn () => strtolower(pathinfo($this->nom, PATHINFO_EXTENSION) ?: pathinfo($this->chemin, PATHINFO_EXTENSION)));
    }

    protected function estImage(): Attribute
    {
        return Attribute::get(fn () => in_array($this->extension, ['jpg', 'jpeg', 'png', 'webp'], true));
    }

    protected function estVisualisable(): Attribute
    {
        return Attribute::get(fn () => in_array($this->extension, self::EXTENSIONS_VISUALISABLES, true));
    }

    /** Pictogramme du type de fichier, pour les listes. */
    protected function icone(): Attribute
    {
        return Attribute::get(fn () => match (true) {
            $this->est_image => '🖼️',
            $this->extension === 'pdf' => '📕',
            in_array($this->extension, ['xls', 'xlsx', 'ods'], true) => '📊',
            in_array($this->extension, ['ppt', 'pptx'], true) => '📽️',
            default => '📄',
        });
    }
}
