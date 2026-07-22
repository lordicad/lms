<?php

namespace Tests\Feature\Admin;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An admin oversees one school. Every one of these asserts the same thing from a different page:
 * what belongs to another school is not visible, and not reachable by guessing a URL.
 */
class SchoolIsolationTest extends TestCase
{
    use RefreshDatabase;

    private School $mine;

    private School $theirs;

    private User $admin;

    private Grade $grade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grade = Grade::factory()->level(3)->create();
        $this->mine = School::factory()->create(['name' => 'SK Bukit Damansara']);
        $this->theirs = School::factory()->create(['name' => 'SK Taman Melati']);

        $this->admin = User::factory()->admin()->create(['school_id' => $this->mine->id]);
    }

    private function teacherAt(School $school, string $name): User
    {
        return User::factory()->teacher()->create(['school_id' => $school->id, 'name' => $name]);
    }

    private function studentAt(School $school, string $name): User
    {
        return User::factory()->student($this->grade->level)->create([
            'school_id' => $school->id, 'name' => $name, 'email' => strtolower(str_replace(' ', '.', $name)).'@moe.edu.my',
        ]);
    }

    public function test_the_user_list_shows_only_this_schools_people(): void
    {
        $this->teacherAt($this->mine, 'Cikgu Sini');
        $this->teacherAt($this->theirs, 'Cikgu Sana');

        $response = $this->actingAs($this->admin)->get(route('admin.pengguna'));

        $response->assertOk()->assertSee('Cikgu Sini')->assertDontSee('Cikgu Sana');
        $this->assertSame(1, $response->viewData('counts')['teacher']);
    }

    public function test_the_student_list_shows_only_this_schools_students(): void
    {
        $this->studentAt($this->mine, 'Murid Sini');
        $this->studentAt($this->theirs, 'Murid Sana');

        $response = $this->actingAs($this->admin)->get(route('admin.murid'));

        $response->assertOk()->assertSee('Murid Sini')->assertDontSee('Murid Sana');
        $this->assertSame(1, $response->viewData('totalStudents'));
    }

    public function test_the_teacher_list_shows_only_this_schools_teachers(): void
    {
        $this->teacherAt($this->mine, 'Cikgu Sini');
        $this->teacherAt($this->theirs, 'Cikgu Sana');

        $response = $this->actingAs($this->admin)->get(route('admin.bakat'));

        $response->assertOk()->assertSee('Cikgu Sini')->assertDontSee('Cikgu Sana');
        $this->assertSame(1, $response->viewData('totalTeachers'));
    }

    public function test_the_content_pages_show_only_this_schools_content(): void
    {
        $chapter = Chapter::factory()->create();

        Lesson::factory()->for($chapter)->create(['teacher_id' => $this->teacherAt($this->mine, 'A')->id, 'title' => 'Video Sini']);
        Lesson::factory()->for($chapter)->create(['teacher_id' => $this->teacherAt($this->theirs, 'B')->id, 'title' => 'Video Sana']);
        Material::factory()->for($chapter)->create(['teacher_id' => $this->teacherAt($this->mine, 'C')->id, 'title' => 'Bahan Sini']);
        Material::factory()->for($chapter)->create(['teacher_id' => $this->teacherAt($this->theirs, 'D')->id, 'title' => 'Bahan Sana']);
        Quiz::factory()->for($chapter)->create(['teacher_id' => $this->teacherAt($this->mine, 'E')->id, 'title' => 'Kuiz Sini']);
        Quiz::factory()->for($chapter)->create(['teacher_id' => $this->teacherAt($this->theirs, 'F')->id, 'title' => 'Kuiz Sana']);

        $this->actingAs($this->admin)->get(route('admin.kandungan.video'))
            ->assertOk()->assertSee('Video Sini')->assertDontSee('Video Sana');

        $this->actingAs($this->admin)->get(route('admin.kandungan.bahan'))
            ->assertOk()->assertSee('Bahan Sini')->assertDontSee('Bahan Sana');

        $this->actingAs($this->admin)->get(route('admin.kandungan.kuiz'))
            ->assertOk()->assertSee('Kuiz Sini')->assertDontSee('Kuiz Sana');
    }

    public function test_the_dashboard_totals_count_only_this_school(): void
    {
        $chapter = Chapter::factory()->create();
        $this->studentAt($this->mine, 'Murid Sini');
        $this->studentAt($this->theirs, 'Murid Sana');
        Lesson::factory()->for($chapter)->create(['teacher_id' => $this->teacherAt($this->mine, 'A')->id]);
        Lesson::factory()->for($chapter)->create(['teacher_id' => $this->teacherAt($this->theirs, 'B')->id]);

        $totals = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->viewData('totals');

        $this->assertSame(1, $totals['students']);
        $this->assertSame(1, $totals['teachers']);
        $this->assertSame(1, $totals['videos']);
    }

    public function test_another_schools_user_cannot_be_opened_by_url(): void
    {
        $outsider = $this->teacherAt($this->theirs, 'Cikgu Sana');

        $this->actingAs($this->admin)->get(route('admin.pengguna.edit', $outsider))->assertNotFound();
    }

    public function test_another_schools_user_cannot_be_edited_or_deleted(): void
    {
        $outsider = $this->teacherAt($this->theirs, 'Cikgu Sana');

        $this->actingAs($this->admin)->put(route('admin.pengguna.update', $outsider), [
            'role' => User::ROLE_TEACHER, 'name' => 'Diubah', 'username' => 'diubah',
            'email' => 'diubah@moe.gov.my', 'is_active' => 1,
        ])->assertNotFound();

        $this->actingAs($this->admin)->delete(route('admin.pengguna.destroy', $outsider))->assertNotFound();
        $this->actingAs($this->admin)->post(route('admin.pengguna.status', $outsider))->assertNotFound();

        $this->assertSame('Cikgu Sana', $outsider->fresh()->name);
    }

    public function test_another_schools_teacher_detail_is_not_reachable(): void
    {
        $outsider = $this->teacherAt($this->theirs, 'Cikgu Sana');

        $this->actingAs($this->admin)->get(route('admin.bakat.show', $outsider))->assertNotFound();
        $this->actingAs($this->admin)->post(route('admin.guru.status', $outsider))->assertNotFound();
    }

    public function test_a_new_account_is_created_in_the_admins_own_school(): void
    {
        $this->actingAs($this->admin)->post(route('admin.pengguna.store'), [
            'role' => User::ROLE_TEACHER,
            'name' => 'Cikgu Baharu',
            'username' => 'baharu',
            'email' => 'baharu@moe.gov.my',
            // Even asked for another school outright, it lands in the admin's own.
            'school_id' => $this->theirs->id,
            'auto_password' => 1,
            'is_active' => 1,
        ])->assertRedirect(route('admin.pengguna'));

        $this->assertSame($this->mine->id, User::where('username', 'baharu')->firstOrFail()->school_id);
    }

    /** Fail closed: an admin with no school is shown nothing rather than everything. */
    public function test_an_admin_without_a_school_sees_nothing(): void
    {
        $this->teacherAt($this->mine, 'Cikgu Sini');
        $this->teacherAt($this->theirs, 'Cikgu Sana');
        $unassigned = User::factory()->admin()->create(['school_id' => null]);

        $response = $this->actingAs($unassigned)->get(route('admin.pengguna'));

        $response->assertOk()->assertDontSee('Cikgu Sini')->assertDontSee('Cikgu Sana');
        $this->assertSame(0, $response->viewData('counts')['teacher']);
    }
}
