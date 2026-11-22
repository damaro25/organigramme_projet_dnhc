<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Intitulé du poste de la DNAH : « Directeur National Adjoint des Hydrocarbures ». */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('agents')
            ->whereIn('poste', ['Directrice Nationale Adjointe des Hydrocarbures', 'Directrice Nationale Adjointe'])
            ->update(['poste' => 'Directeur National Adjoint des Hydrocarbures']);
    }

    public function down(): void
    {
        // Pas de retour arrière : l'ancien intitulé était erroné.
    }
};
