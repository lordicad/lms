<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'short_name' => null,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 1_000_000),
            'category' => 'teras',
            'color' => fake()->hexColor(),
            'icon' => '📘',
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    /** Make this subject available (offered) in the given Tahun via the grade_subject pivot. */
    public function availableIn(Grade $grade): static
    {
        return $this->afterCreating(fn (Subject $subject) => $subject->grades()->syncWithoutDetaching([$grade->id]));
    }
}
