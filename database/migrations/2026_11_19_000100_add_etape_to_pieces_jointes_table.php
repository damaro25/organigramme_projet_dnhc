<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Pièces jointes d'une étape : justificatifs joints lors de sa validation. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pieces_jointes', function (Blueprint $table) {
            $table->foreignId('etape_id')->nullable()->after('projet_id')->constrained('etapes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pieces_jointes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('etape_id');
        });
    }
};
