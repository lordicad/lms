<?php

namespace Tests\Feature\Student;

use App\Models\Chapter;
use App\Models\Favourite;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\LessonView;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The two panels beside the student profile.
 *
 * Both show only what the app records. There is no per-student download log — materials keep an
 * aggregate count and nothing else — so downloads are absent by design, and these tests are as much
 * about what is not shown as what is.
 */
class ProfilePanelsTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Chapter $chapter;

    protected function setUp(): void
    {
        parent::setUp();
        $grade = Grade::factory()->level(4)->create();
        $this->chapter = Chapter::factory()->create(['grade_id' => $grade->id]);
        $this->student = User::factory()->student(4)->create(['grade_id' => $grade->id]);
    }

    private function page(): string
    {
        return $this->actingAs($this->student)->get(route('profile.edit'))->assertOk()->getContent();
    }

    private function lesson(string $title, bool $published = true): Lesson
    {
        return Lesson::factory()->for($this->chapter)->create([
            'title' => $title,
            'is_published' => $published,
        ]);
    }

    public function test_a_watched_video_appears_in_recent_activity(): void
    {
        LessonView::create([
            'lesson_id' => $this->lesson('Kaedah Mendarab Pecahan')->id,
            'student_id' => $this->student->id,
        ]);

        $this->assertStringContainsString('Kaedah Mendarab Pecahan', $this->page());
    }

    public function test_a_finished_quiz_appears_with_its_score(): void
    {
        $quiz = Quiz::factory()->for($this->chapter)->create(['title' => 'Kuiz Bab 1']);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'score' => 8,
            'max_score' => 10,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $html = $this->page();

        $this->assertStringContainsString('Kuiz Bab 1', $html);
        $this->assertStringContainsString('80', $html, 'the score percentage is not shown');
    }

    public function test_a_saved_video_appears(): void
    {
        Favourite::create([
            'lesson_id' => $this->lesson('Pecahan Setara')->id,
            'student_id' => $this->student->id,
        ]);

        $this->assertStringContainsString('Pecahan Setara', $this->page());
    }

    /**
     * Another student's activity is theirs alone.
     *
     * The video itself may still be suggested — it is published, in this student's Year and
     * unwatched by them — so what must not happen is it appearing as something THEY did. With no
     * activity of their own, the panel has to say so.
     */
    public function test_someone_elses_activity_is_not_shown(): void
    {
        $other = User::factory()->student(4)->create();

        LessonView::create([
            'lesson_id' => $this->lesson('Video Murid Lain')->id,
            'student_id' => $other->id,
        ]);

        $html = $this->page();

        $this->assertStringContainsString(e(__('Belum ada aktiviti. Tonton video atau jawab kuiz untuk bermula.')), $html);
        $this->assertStringNotContainsString(e(__('Ditonton')), $html, "another student's view leaked in");
    }

    public function test_the_panel_says_so_when_there_is_no_activity(): void
    {
        $this->assertStringContainsString(e(__('Belum ada aktiviti. Tonton video atau jawab kuiz untuk bermula.')), $this->page());
    }

    /** Recommendations are unwatched published videos from the student's own Year. */
    public function test_an_unwatched_video_in_their_year_is_recommended(): void
    {
        $this->lesson('Bahagi Pecahan');

        $this->assertStringContainsString('Bahagi Pecahan', $this->page());
    }

    public function test_a_video_already_watched_is_not_recommended(): void
    {
        $seen = $this->lesson('Sudah Ditonton');
        LessonView::create(['lesson_id' => $seen->id, 'student_id' => $this->student->id]);

        $html = $this->page();

        // It is in recent activity, so it appears once — but not a second time as a suggestion.
        $this->assertSame(1, substr_count($html, 'Sudah Ditonton'), 'a watched video was recommended again');
    }

    public function test_an_unpublished_video_is_not_recommended(): void
    {
        $this->lesson('Masih Draf', published: false);

        $this->assertStringNotContainsString('Masih Draf', $this->page());
    }

    public function test_a_video_from_another_year_is_not_recommended(): void
    {
        $otherGrade = Grade::factory()->level(6)->create();
        $otherChapter = Chapter::factory()->create(['grade_id' => $otherGrade->id]);
        Lesson::factory()->for($otherChapter)->create(['title' => 'Tahun Lain', 'is_published' => true]);

        $this->assertStringNotContainsString('Tahun Lain', $this->page());
    }

    /** Everything that was on the page before is still on it. */
    public function test_the_existing_sections_are_all_still_there(): void
    {
        $html = $this->page();

        foreach ([
            __('Lencana Saya'),
            __('Maklumat akaun'),
            __('Tukar kata laluan'),
            __('Jumlah mata'),
            __('Kuiz selesai'),
            __('Video ditonton'),
        ] as $section) {
            $this->assertStringContainsString(e($section), $html, "the page lost: {$section}");
        }

        $this->assertStringContainsString(route('logout'), $html, 'the log out form is gone');
    }
}
