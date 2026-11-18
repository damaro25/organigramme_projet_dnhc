<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Enregistre la photo d'un agent : recadrage carré, redimensionnement,
 * compression WebP et miniature. L'ancienne photo est supprimée du disque.
 */
class PhotoService
{
    public const TAILLE = 512;
    public const TAILLE_MINIATURE = 128;
    public const QUALITE = 82;
    public const DOSSIER = 'agents';

    private ImageManager $images;

    public function __construct()
    {
        $this->images = new ImageManager(new Driver);
    }

    public function remplacer(Agent $agent, UploadedFile $fichier): Agent
    {
        $disque = Storage::disk('public');
        $base = self::DOSSIER.'/'.$agent->id.'-'.Str::random(10);

        // cover() recadre au centre si l'image reçue n'est pas déjà carrée (recadrage Cropper.js côté navigateur).
        $image = $this->images->read($fichier->getRealPath())->cover(self::TAILLE, self::TAILLE);
        $disque->put("{$base}.webp", (string) $image->toWebp(self::QUALITE));

        $miniature = $this->images->read($fichier->getRealPath())->cover(self::TAILLE_MINIATURE, self::TAILLE_MINIATURE);
        $disque->put("{$base}-min.webp", (string) $miniature->toWebp(self::QUALITE));

        $anciens = array_filter([$agent->photo, $agent->photo_miniature]);

        $agent->update(['photo' => "{$base}.webp", 'photo_miniature' => "{$base}-min.webp"]);

        $disque->delete($anciens);

        return $agent;
    }

    /** Supprime la photo : l'agent retrouve l'avatar par défaut. */
    public function supprimer(Agent $agent): Agent
    {
        Storage::disk('public')->delete(array_filter([$agent->photo, $agent->photo_miniature]));
        $agent->update(['photo' => null, 'photo_miniature' => null]);

        return $agent;
    }
}
