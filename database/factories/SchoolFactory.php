<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'SK '.ucfirst(fake()->unique()->words(2, true)),
            'code' => strtoupper(fake()->unique()->bothify('???####')),
            'state' => fake()->randomElement([
                'Selangor', 'Johor', 'Perak', 'Kedah', 'Pahang', 'Sabah', 'Sarawak', 'Melaka',
            ]),
        ];
    }
}
