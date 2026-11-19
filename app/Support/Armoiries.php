<?php

namespace App\Support;

/**
 * Armoiries de la République de Guinée, utilisées dans l'en-tête, les PDF et
 * l'icône des onglets du navigateur (charte graphique du Gouvernement).
 *
 * Le fichier officiel se dépose dans public/images/armoiries.svg ou .png. Tant
 * qu'il est absent, l'application affiche des emplacements provisoires.
 */
class Armoiries
{
    public const FICHIERS = ['images/armoiries.svg', 'images/armoiries.png'];

    public const PROVISOIRE = 'images/logo-ministere.svg';

    /** Chemin relatif à public/ du fichier officiel, ou null s'il n'a pas été déposé. */
    public static function fichier(): ?string
    {
        foreach (self::FICHIERS as $fichier) {
            if (is_file(public_path($fichier))) {
                return $fichier;
            }
        }

        return null;
    }

    /** Chemin relatif à public/ de l'image à afficher (officielle ou provisoire). */
    public static function image(): string
    {
        return self::fichier() ?? self::PROVISOIRE;
    }

    /** URL de l'image, avec un numéro de version pour que les navigateurs voient le remplacement. */
    public static function url(): string
    {
        $fichier = self::image();

        return asset($fichier).'?v='.filemtime(public_path($fichier));
    }

    /** Type MIME de l'icône d'onglet. */
    public static function typeIcone(): string
    {
        return str_ends_with(self::fichier() ?? '', '.png') ? 'image/png' : 'image/svg+xml';
    }
}
