<?php

namespace Database\Factories;

use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Projet> */
class ProjetFactory extends Factory
{
    public function definition(): array
    {
        $debut = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'titre' => 'Projet '.fake()->unique()->words(3, true),
            'description' => fake('fr_FR')->sentence(12),
            'structure_id' => Structure::factory(),
            'date_debut' => $debut,
            'date_fin_prevue' => fake()->dateTimeBetween($debut, '+1 year'),
            'statut' => 'en_cours',
            'archive' => false,
        ];
    }
}
