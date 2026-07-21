<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\ProfileBackfillSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileBackfillTest extends TestCase
{
    use RefreshDatabase;

    private Grade $grade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grade = Grade::factory()->level(3)->create();
        Subject::factory()->count(3)->create();

        $school = School::factory()->create();
        SchoolClass::factory()->for($school)->state(['grade_id' => $this->grade->id, 'name' => 'Bestari'])->create();
    }

    private function backfill(): void
    {
        $this->seed(ProfileBackfillSeeder::class);
    }

    public function test_it_fills_the_empty_teacher_fields(): void
    {
        $teacher = User::factory()->teacher()->create([
            'school_id' => null, 'phone' => null, 'position' => null,
        ]);

        $this->backfill();
        $teacher->refresh();

        $this->assertNotNull($teacher->school_id);
        $this->assertNotNull($teacher->phone);
        $this->assertNotNull($teacher->position);
        $this->assertTrue($teacher->subjects()->exists());
    }

    public function test_it_fills_the_empty_student_fields_with_a_class_of_their_own_year(): void
    {
        $student = User::factory()->student($this->grade->level)->create([
            'email' => null, 'school_id' => null, 'school_class_id' => null,
            'guardian_name' => null, 'guardian_phone' => null, 'guardian_email' => null,
        ]);

        $this->backfill();
        $student->refresh();

        $this->assertNotNull($student->email);
        $this->assertNotNull($student->guardian_name);
        $this->assertNotNull($student->guardian_phone);
        $this->assertNotNull($student->guardian_email);

        // The class must belong to the student's own school and own year.
        $class = $student->schoolClass;
        $this->assertNotNull($class);
        $this->assertSame($student->school_id, $class->school_id);
        $this->assertSame($student->grade_id, $class->grade_id);
    }

    /** The whole point: it must be safe to run against a live database. */
    public function test_it_never_overwrites_details_someone_already_entered(): void
    {
        $teacher = User::factory()->teacher()->create([
            'phone' => '011-111 1111',
            'position' => 'Guru Besar',
            'school_id' => School::factory()->create()->id,
        ]);
        $original = $teacher->school_id;

        $this->backfill();
        $teacher->refresh();

        $this->assertSame('011-111 1111', $teacher->phone);
        $this->assertSame('Guru Besar', $teacher->position);
        $this->assertSame($original, $teacher->school_id);
    }

    public function test_running_it_twice_changes_nothing_the_second_time(): void
    {
        User::factory()->teacher()->create(['school_id' => null, 'phone' => null, 'position' => null]);
        User::factory()->student($this->grade->level)->create(['school_id' => null, 'guardian_name' => null]);

        $this->backfill();
        $after = User::orderBy('id')->get()->map->only(['id', 'school_id', 'phone', 'position', 'guardian_name', 'school_class_id']);

        $this->backfill();
        $again = User::orderBy('id')->get()->map->only(['id', 'school_id', 'phone', 'position', 'guardian_name', 'school_class_id']);

        $this->assertEquals($after, $again);
    }

    public function test_it_gives_each_class_a_homeroom_teacher_from_its_own_school(): void
    {
        $school = School::first();
        User::factory()->teacher()->create(['school_id' => $school->id]);

        $this->backfill();

        $class = SchoolClass::first();
        $this->assertNotNull($class->homeroom_teacher_id);
        $this->assertSame($school->id, $class->homeroomTeacher->school_id);
    }
}
