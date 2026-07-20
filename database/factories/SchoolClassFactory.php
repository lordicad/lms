<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'grade_id' => fn () => Grade::query()->inRandomOrder()->value('id')
                ?? Grade::factory()->create()->id,
            'name' => fake()->unique()->randomElement([
                'Bestari', 'Cerdik', 'Bijak', 'Gemilang', 'Cemerlang', 'Dinamik', 'Jaya', 'Mutiara',
            ]),
            'homeroom_teacher_id' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function homeroom(User $teacher): static
    {
        return $this->state(fn () => ['homeroom_teacher_id' => $teacher->id]);
    }
}
