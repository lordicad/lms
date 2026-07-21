<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
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
            // An established account by default: it owns its password, so the first-password
            // screen does not hold it. Use ->adminIssued() for a freshly handed-over account.
            'password_changed_at' => now(),
            'role' => User::ROLE_STUDENT,
            'grade_id' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /** Created by an admin and not yet opened by its owner — still on the handed-over password. */
    public function adminIssued(): static
    {
        return $this->state(fn () => ['password_changed_at' => null]);
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

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_ADMIN,
            'grade_id' => null,
        ]);
    }

    public function atSchool(School $school): static
    {
        return $this->state(fn () => ['school_id' => $school->id]);
    }

    public function inClass(SchoolClass $class): static
    {
        return $this->state(fn () => [
            'school_id' => $class->school_id,
            'school_class_id' => $class->id,
            'grade_id' => $class->grade_id,
        ]);
    }

    /** Attach the given subjects to this teacher via the subject_teacher pivot. */
    public function teaches(Subject ...$subjects): static
    {
        return $this->afterCreating(fn (User $user) => $user->subjects()->syncWithoutDetaching(
            collect($subjects)->pluck('id')->all()
        ));
    }
}
