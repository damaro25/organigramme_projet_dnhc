<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('poste');
            $table->foreignId('structure_id')->constrained('structures')->restrictOnDelete();
            $table->foreignId('superieur_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->string('email')->nullable()->unique();
            $table->string('telephone', 30)->nullable();
            $table->string('photo')->nullable();
            $table->string('photo_miniature')->nullable();
            $table->boolean('est_responsable')->default(false);
            $table->boolean('actif')->default(true);
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();

            $table->index(['structure_id', 'actif']);
            $table->index(['superieur_id', 'ordre']);
            $table->index(['nom', 'prenom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
