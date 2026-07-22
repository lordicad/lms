<?php

namespace Tests\Feature\Admin;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Grade $grade;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->grade = Grade::factory()->level(3)->create();
        $this->admin = User::factory()->admin()->create(['school_id' => $this->school->id]);
    }

    private function teacher(string $name, string $email): User
    {
        return User::factory()->teacher()->create([
            'name' => $name, 'email' => $email, 'school_id' => $this->school->id,
        ]);
    }

    // --- Videos ---------------------------------------------------------------------

    public function test_videos_can_be_searched_by_title(): void
    {
        $chapter = Chapter::factory()->create();
        $teacher = $this->teacher('Cikgu Ali', 'ali@moe.gov.my');
        Lesson::factory()->for($chapter)->create(['teacher_id' => $teacher->id, 'title' => 'Pendaraban Asas']);
        Lesson::factory()->for($chapter)->create(['teacher_id' => $teacher->id, 'title' => 'Kata Nama']);

        $this->actingAs($this->admin)->get(route('admin.kandungan.video', ['q' => 'Pendaraban']))
            ->assertOk()
            ->assertSee('Pendaraban Asas')
            ->assertDontSee('Kata Nama');
    }

    /** An admin often knows who posted it rather than what it was called. */
    public function test_videos_can_be_searched_by_the_teacher_who_posted_them(): void
    {
        $chapter = Chapter::factory()->create();
        Lesson::factory()->for($chapter)->create([
            'teacher_id' => $this->teacher('Rohana Osman', 'rohana@moe.gov.my')->id, 'title' => 'Video Rohana',
        ]);
        Lesson::factory()->for($chapter)->create([
            'teacher_id' => $this->teacher('Suhaimi Idris', 'suhaimi@moe.gov.my')->id, 'title' => 'Video Suhaimi',
        ]);

        $this->actingAs($this->admin)->get(route('admin.kandungan.video', ['q' => 'Rohana']))
            ->assertOk()
            ->assertSee('Video Rohana')
            ->assertDontSee('Video Suhaimi');
    }

    /** The summary cards must describe the rows on screen, not the whole library. */
    public function test_the_video_counts_follow_the_search(): void
    {
        $chapter = Chapter::factory()->create();
        $teacher = $this->teacher('Cikgu Ali', 'ali@moe.gov.my');
        Lesson::factory()->count(3)->for($chapter)->create(['teacher_id' => $teacher->id, 'title' => 'Sains Asas']);
        Lesson::factory()->for($chapter)->create(['teacher_id' => $teacher->id, 'title' => 'Lain']);

        $total = $this->actingAs($this->admin)
            ->get(route('admin.kandungan.video', ['q' => 'Sains']))
            ->assertOk()
            ->viewData('totalCount');

        $this->assertSame(3, $total);
    }

    // --- Teachers -------------------------------------------------------------------

    public function test_teachers_can_be_searched_by_name_or_email(): void
    {
        $this->teacher('Rohana Osman', 'rohana.osman@moe.gov.my');
        $this->teacher('Suhaimi Idris', 'suhaimi.idris@moe.gov.my');

        $this->actingAs($this->admin)->get(route('admin.bakat', ['q' => 'Rohana']))
            ->assertOk()->assertSee('Rohana Osman')->assertDontSee('Suhaimi Idris');

        $this->actingAs($this->admin)->get(route('admin.bakat', ['q' => 'suhaimi.idris@']))
            ->assertOk()->assertSee('Suhaimi Idris')->assertDontSee('Rohana Osman');
    }

    // --- Students -------------------------------------------------------------------

    public function test_students_can_be_searched_by_name_nickname_or_email(): void
    {
        User::factory()->student($this->grade->level)->create([
            'name' => 'Nurul Ain', 'username' => 'Ain', 'email' => 'ain@moe.edu.my',
            'school_id' => $this->school->id,
        ]);
        User::factory()->student($this->grade->level)->create([
            'name' => 'Zara Idris', 'username' => 'Zara', 'email' => 'zara@moe.edu.my',
            'school_id' => $this->school->id,
        ]);

        foreach (['Nurul', 'Ain', 'ain@moe'] as $term) {
            $this->actingAs($this->admin)->get(route('admin.murid', ['q' => $term]))
                ->assertOk()->assertSee('Nurul Ain')->assertDontSee('Zara Idris');
        }
    }

    /** Searching must not quietly widen the school scope. */
    public function test_search_never_reaches_into_another_school(): void
    {
        $elsewhere = School::factory()->create();
        User::factory()->teacher()->create(['name' => 'Cikgu Sana', 'school_id' => $elsewhere->id]);
        User::factory()->student($this->grade->level)->create([
            'name' => 'Murid Sana', 'school_id' => $elsewhere->id, 'email' => 'sana@moe.edu.my',
        ]);

        $this->actingAs($this->admin)->get(route('admin.bakat', ['q' => 'Sana']))
            ->assertOk()->assertDontSee('Cikgu Sana');

        $this->actingAs($this->admin)->get(route('admin.murid', ['q' => 'Sana']))
            ->assertOk()->assertDontSee('Murid Sana');
    }
}
