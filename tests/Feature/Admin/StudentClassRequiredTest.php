<?php

namespace Tests\Feature\Admin;

use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A student account must be created with a class.
 *
 * Without one they also have no homeroom teacher, and nothing in the app can give them either
 * afterwards: both profiles are read-only on these fields, and the only other filler is
 * ProfileBackfillSeeder, which has to be run by hand on the server.
 */
class StudentClassRequiredTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'role' => User::ROLE_STUDENT,
            'name' => 'Nur Aisyah',
            'username' => 'Aisyah',
            'email' => 'aisyah@moe.edu.my',
            'guardian_name' => 'Puan Salmah',
            'guardian_email' => 'salmah@example.com',
            'auto_password' => 1,
            'is_active' => 1,
        ], $overrides);
    }

    public function test_a_student_cannot_be_created_without_a_class(): void
    {
        $admin = User::factory()->admin()->create();
        $grade = Grade::factory()->level(3)->create();

        $this->actingAs($admin)
            ->post(route('admin.pengguna.store'), $this->payload(['grade_level' => $grade->level]))
            ->assertSessionHasErrors('school_class_id');

        $this->assertDatabaseMissing('users', ['email' => 'aisyah@moe.edu.my']);
    }

    public function test_a_student_is_created_when_a_class_is_chosen(): void
    {
        $admin = User::factory()->admin()->create();
        $grade = Grade::factory()->level(3)->create();
        $class = SchoolClass::factory()->create([
            'school_id' => $admin->school_id,
            'grade_id' => $grade->id,
        ]);

        $this->actingAs($admin)->post(route('admin.pengguna.store'), $this->payload([
            'grade_level' => $grade->level,
            'school_class_id' => $class->id,
        ]))->assertSessionHasNoErrors();

        $this->assertSame($class->id, User::firstWhere('email', 'aisyah@moe.edu.my')->school_class_id);
    }

    /**
     * The account's school comes from the admin's own scope rather than the posted field, so the
     * class has to be checked against that same school. Checking the posted value instead rejected
     * a class that was in the admin's school all along.
     */
    public function test_a_class_in_the_admins_own_school_is_accepted_without_a_posted_school(): void
    {
        $admin = User::factory()->admin()->create();
        $grade = Grade::factory()->level(3)->create();
        $class = SchoolClass::factory()->create([
            'school_id' => $admin->school_id,
            'grade_id' => $grade->id,
        ]);

        $this->actingAs($admin)->post(route('admin.pengguna.store'), $this->payload([
            'grade_level' => $grade->level,
            'school_class_id' => $class->id,
            // No school_id: the scoped admin's school is used regardless of what the form posts.
        ]))->assertSessionHasNoErrors();
    }

    /** Still rejected when the class belongs to a different school entirely. */
    public function test_a_class_from_another_school_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $grade = Grade::factory()->level(3)->create();
        $elsewhere = SchoolClass::factory()->create([
            'school_id' => School::factory()->create()->id,
            'grade_id' => $grade->id,
        ]);

        $this->actingAs($admin)->post(route('admin.pengguna.store'), $this->payload([
            'grade_level' => $grade->level,
            'school_class_id' => $elsewhere->id,
        ]))->assertSessionHasErrors('school_class_id');
    }

    /** The class must match the student's year, not just their school. */
    public function test_a_class_from_another_year_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        Grade::factory()->level(3)->create();
        $otherYear = Grade::factory()->level(6)->create();
        $class = SchoolClass::factory()->create([
            'school_id' => $admin->school_id,
            'grade_id' => $otherYear->id,
        ]);

        $this->actingAs($admin)->post(route('admin.pengguna.store'), $this->payload([
            'grade_level' => 3,
            'school_class_id' => $class->id,
        ]))->assertSessionHasErrors('school_class_id');
    }

    /** Teachers are unaffected — their class field is cleared on save either way. */
    public function test_a_teacher_still_needs_no_class(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.pengguna.store'), [
            'role' => User::ROLE_TEACHER,
            'name' => 'Cikgu Rohana',
            'username' => 'Rohana',
            'email' => 'rohana@moe.gov.my',
            'auto_password' => 1,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertNull(User::firstWhere('email', 'rohana@moe.gov.my')->school_class_id);
    }
}
