<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structures', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('sigle', 30)->nullable();
            $table->enum('type', ['direction', 'service_rattache', 'division', 'section', 'service_deconcentre']);
            $table->enum('groupe', ['appui_technique', 'personnel_appui'])->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('structures')->restrictOnDelete();
            $table->enum('type_lien', ['hierarchique', 'fonctionnel'])->default('hierarchique');
            $table->string('couleur', 7)->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();

            $table->index(['parent_id', 'ordre']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('structures');
    }
};
