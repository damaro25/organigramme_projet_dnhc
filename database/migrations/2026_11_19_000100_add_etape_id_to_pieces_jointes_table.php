<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une pièce jointe peut être rattachée à une étape (justificatifs fournis lors de sa validation).
 * Sans étape, elle reste un document général du projet.
 */
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
