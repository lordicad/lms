<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Database\Seeders\ProfileBackfillSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * This seeder is now the only thing that puts a student in a class or gives a class a homeroom
 * teacher: the teacher and student profiles no longer offer either, and the admin user form sets
 * school_class_id for students but never touches school_classes.homeroom_teacher_id.
 *
 * So it is worth holding to its contract rather than trusting it.
 */
class ProfileBackfillSeederTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: School, 1: Grade} */
    private function schoolWithClasses(string $name, int $level, int $classes = 2): array
    {
        $school = School::factory()->create(['name' => $name]);
        $grade = Grade::firstWhere('level', $level) ?? Grade::factory()->level($level)->create();

        SchoolClass::factory()->count($classes)->for($school)->state(['grade_id' => $grade->id])->create();

        return [$school, $grade];
    }

    public function test_every_student_lands_in_a_class_of_their_own_school_and_year(): void
    {
        [$school, $grade] = $this->schoolWithClasses('SK Bukit Damansara', 3);

        $students = User::factory()->count(5)->student(3)->create([
            'school_id' => $school->id,
            'grade_id' => $grade->id,
            'school_class_id' => null,
        ]);

        $this->seed(ProfileBackfillSeeder::class);

        foreach ($students as $student) {
            $class = $student->fresh()->schoolClass;

            $this->assertNotNull($class, 'a student was left without a class');
            $this->assertSame($school->id, $class->school_id, 'a student was put in another school\'s class');
            $this->assertSame($grade->id, $class->grade_id, 'a student was put in the wrong year');
        }
    }

    /** A class in one school must never be handed a teacher from another. */
    public function test_every_class_gets_a_homeroom_teacher_from_its_own_school(): void
    {
        [$a] = $this->schoolWithClasses('SK Bukit Damansara', 3);
        [$b] = $this->schoolWithClasses('SK Taman Tun', 4);

        User::factory()->count(4)->teacher()->create(['school_id' => $a->id]);
        User::factory()->count(4)->teacher()->create(['school_id' => $b->id]);

        $this->seed(ProfileBackfillSeeder::class);

        foreach (SchoolClass::all() as $class) {
            $this->assertNotNull($class->homeroom_teacher_id, "class {$class->id} has no homeroom teacher");
            $this->assertSame(
                $class->school_id,
                $class->homeroomTeacher->school_id,
                'a homeroom teacher was drawn from a different school',
            );
        }
    }

    /** One teacher, one homeroom — the seeder skips anyone already running a class. */
    public function test_a_teacher_is_not_given_two_homerooms(): void
    {
        [$school] = $this->schoolWithClasses('SK Bukit Damansara', 3, classes: 3);
        User::factory()->count(3)->teacher()->create(['school_id' => $school->id]);

        $this->seed(ProfileBackfillSeeder::class);

        $assigned = SchoolClass::whereNotNull('homeroom_teacher_id')->pluck('homeroom_teacher_id');

        $this->assertSame($assigned->count(), $assigned->unique()->count(), 'a teacher runs two homerooms');
    }

    /** Idempotent: it fills blanks, and a second run must not shuffle what the admin has set. */
    public function test_running_it_again_changes_nothing(): void
    {
        [$school, $grade] = $this->schoolWithClasses('SK Bukit Damansara', 3);
        User::factory()->count(3)->teacher()->create(['school_id' => $school->id]);
        User::factory()->count(4)->student(3)->create([
            'school_id' => $school->id, 'grade_id' => $grade->id, 'school_class_id' => null,
        ]);

        $this->seed(ProfileBackfillSeeder::class);

        $classes = SchoolClass::orderBy('id')->pluck('homeroom_teacher_id', 'id');
        $students = User::where('role', User::ROLE_STUDENT)->orderBy('id')->pluck('school_class_id', 'id');

        $this->seed(ProfileBackfillSeeder::class);

        $this->assertEquals($classes, SchoolClass::orderBy('id')->pluck('homeroom_teacher_id', 'id'));
        $this->assertEquals($students, User::where('role', User::ROLE_STUDENT)->orderBy('id')->pluck('school_class_id', 'id'));
    }

    /** With no schools it must bail rather than half-fill anything. */
    public function test_it_does_nothing_when_there_are_no_schools(): void
    {
        $student = User::factory()->student(3)->create(['school_id' => null, 'school_class_id' => null]);
        School::query()->delete();

        $this->seed(ProfileBackfillSeeder::class);

        $this->assertNull($student->fresh()->school_class_id);
    }
}
