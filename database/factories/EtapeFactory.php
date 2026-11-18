<?php

namespace Database\Factories;

use App\Models\Projet;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Etape> */
class EtapeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'projet_id' => Projet::factory(),
            'libelle' => ucfirst(fake('fr_FR')->words(4, true)),
            'poids' => 1,
            'est_terminee' => false,
        ];
    }

    public function terminee(): static
    {
        return $this->state(fn () => ['est_terminee' => true, 'date_realisation' => now()->subDays(rand(1, 60))]);
    }
}
