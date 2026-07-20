<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'chapter_id' => Chapter::factory(),
            'teacher_id' => User::factory()->teacher(),
            'title' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(),
            'type' => Quiz::TYPE_INTERACTIVE,
            'file_path' => null,
            'original_name' => null,
            'duration_minutes' => fake()->numberBetween(5, 30),
            'is_published' => true,
        ];
    }

    public function file(): static
    {
        return $this->state(fn () => [
            'type' => Quiz::TYPE_FILE,
            'file_path' => 'quizzes/'.fake()->uuid().'.pdf',
            'original_name' => fake()->words(2, true).'.pdf',
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
