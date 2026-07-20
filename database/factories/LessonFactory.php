<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'chapter_id' => Chapter::factory(),
            'teacher_id' => User::factory()->teacher(),
            'title' => ucfirst(fake()->words(4, true)),
            'description' => fake()->sentence(),
            'source' => Lesson::SOURCE_UPLOAD,
            'video_path' => 'videos/'.fake()->uuid().'.mp4',
            'youtube_id' => null,
            'thumbnail_path' => null,
            'duration_seconds' => fake()->numberBetween(120, 1800),
            'ownership' => Lesson::OWNERSHIP_UPLOAD,
            'counts_for_talent' => true,
            'is_published' => true,
        ];
    }

    public function youtube(): static
    {
        return $this->state(fn () => [
            'source' => Lesson::SOURCE_YOUTUBE,
            'youtube_id' => fake()->regexify('[A-Za-z0-9_-]{11}'),
            'video_path' => null,
            'ownership' => Lesson::OWNERSHIP_OWNED,
            'counts_for_talent' => true,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }

    public function views(int $count): static
    {
        return $this->state(fn () => ['views_count' => $count]);
    }
}
