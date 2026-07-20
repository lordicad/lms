<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'student_id' => User::factory()->student(),
            'score' => 80,
            'max_score' => 100,
            'correct_count' => 8,
            'question_count' => 10,
            'counts_for_ranking' => false,
            'started_at' => now()->subMinutes(6),
            'completed_at' => now(),
            'duration_seconds' => 360,
        ];
    }

    /** A completed attempt at or above the 80% pass mark. */
    public function passed(): static
    {
        return $this->state(fn () => [
            'score' => 90,
            'max_score' => 100,
            'correct_count' => 9,
            'question_count' => 10,
            'completed_at' => now(),
        ]);
    }

    /** A completed attempt below the pass mark. */
    public function failed(): static
    {
        return $this->state(fn () => [
            'score' => 40,
            'max_score' => 100,
            'correct_count' => 4,
            'question_count' => 10,
            'completed_at' => now(),
        ]);
    }

    /** The first completed attempt that feeds the leaderboard. */
    public function ranked(): static
    {
        return $this->state(fn () => ['counts_for_ranking' => true]);
    }

    /** A started-but-not-finished attempt. */
    public function incomplete(): static
    {
        return $this->state(fn () => ['completed_at' => null]);
    }
}
