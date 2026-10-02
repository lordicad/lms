<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_icon_is_defined_once(): void
    {
        preg_match_all("/^\s*'([a-z0-9-]+)' =>/m", file_get_contents(resource_path('views/components/icon.blade.php')), $m);

        $dupes = array_keys(array_filter(array_count_values($m[1]), fn ($n) => $n > 1));

        $this->assertSame([], $dupes, 'a second match arm is dead code: PHP returns the first');
    }

    public function test_the_night_mode_toggle_is_named_consistently(): void
    {
        foreach (['components/cikgu-layout', 'components/admin-layout', 'layouts/student'] as $view) {
            $this->assertStringNotContainsString("__('Mod Malam')", file_get_contents(resource_path("views/{$view}.blade.php")), $view);
        }
    }

    private function studentWithFinishedQuizzes(int $count): User
    {
        $grade = Grade::factory()->level(3)->create();
        $subject = Subject::factory()->availableIn($grade)->create();
        $chapter = Chapter::factory()->create(['subject_id' => $subject->id, 'grade_id' => $grade->id, 'is_active' => true]);
        $student = User::factory()->student(3)->create(['grade_id' => $grade->id]);

        Quiz::factory()->for($chapter)->count($count)->create()->each(fn ($quiz) => QuizAttempt::factory()->for($quiz)->ranked()->create([
            'student_id' => $student->id, 'score' => 50, 'max_score' => 100, 'completed_at' => now(),
        ]));

        return $student;
    }

    public function test_view_all_expands_the_completed_list_when_some_rows_are_hidden(): void
    {
        $html = $this->actingAs($this->studentWithFinishedQuizzes(7))->get(route('kuiz-saya.index'))->assertOk()->getContent();

        $this->assertStringContainsString('@click="all = ! all"', $html);
        $this->assertSame(2, substr_count($html, 'x-show="all" x-cloak'), 'rows 6 and 7 start hidden');
        $this->assertStringContainsString(e(__('Lihat semua kuiz')).' (7)', $html);
    }

    public function test_view_all_is_not_shown_when_everything_already_fits(): void
    {
        $html = $this->actingAs($this->studentWithFinishedQuizzes(3))->get(route('kuiz-saya.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('@click="all = ! all"', $html);
        $this->assertStringNotContainsString(e(__('Lihat semua kuiz')), $html);
    }

    public function test_the_quiz_duration_unit_translates(): void
    {
        $grade = Grade::factory()->level(3)->create();
        $chapter = Chapter::factory()->create(['grade_id' => $grade->id]);
        $quiz = Quiz::factory()->for($chapter)->create(['duration_minutes' => 15]);
        $student = User::factory()->student(3)->create(['grade_id' => $grade->id]);

        app()->setLocale('en');
        $this->assertSame('15 minutes', $quiz->duration_minutes.' '.__('minit'));

        $html = $this->actingAs($student)->withSession(['locale' => 'en'])->get(route('kuiz.intro', $quiz))->assertOk()->getContent();
        $this->assertStringNotContainsString('15 minit', $html);
    }
}
