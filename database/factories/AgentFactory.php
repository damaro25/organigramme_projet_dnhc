<?php

namespace Database\Factories;

use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Agent> */
class AgentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => strtoupper(fake('fr_FR')->lastName()),
            'prenom' => fake('fr_FR')->firstName(),
            'poste' => "Chargé d'études",
            'structure_id' => Structure::factory(),
            'superieur_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'telephone' => fake('fr_FR')->phoneNumber(),
            'est_responsable' => false,
            'actif' => true,
            'ordre' => 0,
        ];
    }

    public function responsable(string $poste = 'Chef de section'): static
    {
        return $this->state(fn () => ['est_responsable' => true, 'poste' => $poste]);
    }
}
