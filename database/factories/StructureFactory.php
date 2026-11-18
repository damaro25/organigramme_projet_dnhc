<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Structure> */
class StructureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => 'Section '.fake()->unique()->words(2, true),
            'sigle' => null,
            'type' => 'section',
            'groupe' => null,
            'parent_id' => null,
            'type_lien' => 'hierarchique',
            'couleur' => null,
            'ordre' => 0,
        ];
    }

    public function division(): static
    {
        return $this->state(fn () => ['type' => 'division', 'nom' => 'Division '.fake()->unique()->word(), 'couleur' => '#1d4ed8']);
    }
}
