<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Identité visuelle DNH : remplace les couleurs par défaut des structures
 * (anciennes valeurs des données de démonstration) par la nouvelle palette.
 * Une couleur choisie à la main dans le menu Structures n'est pas touchée.
 */
return new class extends Migration
{
    /** ancienne couleur par défaut => nouvelle */
    private const CORRESPONDANCES = [
        // Charte du Gouvernement (étape 9)
        '#006a45' => '#000b3d', '#009460' => '#263f95', '#ce1126' => '#b45309',
        '#a37f00' => '#0f766e', '#6b7280' => '#64748b', '#f3f4f6' => '#f1f5f9',
        // Valeurs d'origine (étapes 3 à 8)
        '#1e3a8a' => '#000b3d', '#0891b2' => '#263f95', '#d97706' => '#b45309',
        '#059669' => '#0f766e', '#e0f2fe' => '#f1f5f9', '#2563eb' => '#13235c',
        '#1d4ed8' => '#1c3079',
    ];

    public function up(): void
    {
        foreach (self::CORRESPONDANCES as $ancienne => $nouvelle) {
            DB::table('structures')->whereRaw('LOWER(couleur) = ?', [$ancienne])->update(['couleur' => $nouvelle]);
        }
    }

    public function down(): void
    {
        // Les anciennes couleurs ne sont pas restaurées (plusieurs sources possibles).
    }
};
