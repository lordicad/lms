<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chapter>
 */
class ChapterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            // Reuse a seeded grade when one exists so the six-Tahun uniqueness is never violated.
            'grade_id' => fn () => Grade::query()->inRandomOrder()->value('id')
                ?? Grade::factory()->create()->id,
            // Globally unique keeps the (subject, grade, number) key intact; stays within smallint.
            'number' => fake()->unique()->numberBetween(1, 60000),
            'title' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(),
            'is_active' => true,
            'created_by' => null,
        ];
    }

    /**
     * Keep the subject↔grade availability pivot consistent with the chapter, so a chapter created
     * in a factory is always a valid (offered) Subject+Tahun combination.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Chapter $chapter) {
            $chapter->subject()->first()?->grades()->syncWithoutDetaching([$chapter->grade_id]);
        });
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
