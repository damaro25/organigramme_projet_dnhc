<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etapes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_id')->constrained('projets')->cascadeOnDelete();
            $table->string('libelle');
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->unsignedTinyInteger('poids')->default(1);
            $table->boolean('est_terminee')->default(false);
            $table->date('date_realisation')->nullable();
            $table->text('commentaire')->nullable();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['projet_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etapes');
    }
};
