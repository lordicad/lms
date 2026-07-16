<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_STUDENT,
            'grade_id' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function teacher(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_TEACHER,
            'grade_id' => null,
        ]);
    }

    /**
     * A student in the given Tahun. Falls back to any seeded grade when none is named.
     */
    public function student(?int $level = null): static
    {
        return $this->state(function () use ($level) {
            $grade = $level
                ? Grade::firstOrCreate(['level' => $level], ['name' => "Tahun {$level}"])
                : Grade::query()->inRandomOrder()->first()
                    ?? Grade::create(['level' => 1, 'name' => 'Tahun 1']);

            return [
                'role' => User::ROLE_STUDENT,
                'grade_id' => $grade->id,
                'email' => null,
            ];
        });
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
