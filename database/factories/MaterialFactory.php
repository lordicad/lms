<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(2, true).'.pdf';

        return [
            'chapter_id' => Chapter::factory(),
            'lesson_id' => null,
            'teacher_id' => User::factory()->teacher(),
            'title' => ucfirst(fake()->words(3, true)),
            'file_path' => 'materials/'.fake()->uuid().'.pdf',
            'original_name' => $name,
            'mime_type' => 'application/pdf',
            'size_kb' => fake()->numberBetween(50, 5000),
        ];
    }

    public function downloads(int $count): static
    {
        return $this->state(fn () => ['download_count' => $count]);
    }
}
