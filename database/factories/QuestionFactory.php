<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'question_text' => ucfirst(fake()->sentence()).'?',
            'question_type' => Question::TYPE_SINGLE,
            'points' => 10,
            'sort_order' => 0,
        ];
    }

    public function multiple(): static
    {
        return $this->state(fn () => ['question_type' => Question::TYPE_MULTIPLE]);
    }
}
