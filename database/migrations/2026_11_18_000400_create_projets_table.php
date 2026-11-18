<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projets', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->foreignId('structure_id')->constrained('structures')->restrictOnDelete();
            $table->date('date_debut')->nullable();
            $table->date('date_fin_prevue')->nullable();
            $table->enum('statut', ['non_demarre', 'en_cours', 'termine', 'suspendu'])->default('non_demarre');
            $table->boolean('archive')->default(false);
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_le')->nullable();
            $table->timestamps();

            $table->index(['structure_id', 'statut', 'archive']);
            $table->index('date_fin_prevue');
        });

        Schema::create('agent_projet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('projet_id')->constrained('projets')->cascadeOnDelete();
            $table->enum('role', ['responsable', 'membre'])->default('membre');
            $table->timestamps();

            $table->unique(['agent_id', 'projet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_projet');
        Schema::dropIfExists('projets');
    }
};
