<?php

namespace Database\Factories;

use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grade>
 */
class GradeFactory extends Factory
{
    public function definition(): array
    {
        $level = fake()->unique()->numberBetween(1, 6);

        return [
            'level' => $level,
            'name' => "Tahun {$level}",
        ];
    }

    public function level(int $level): static
    {
        return $this->state(fn () => [
            'level' => $level,
            'name' => "Tahun {$level}",
        ]);
    }
}
