<?php

namespace Tests\Feature\Teacher;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\LeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingOwnQuizzesTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    private User $otherTeacher;

    private Quiz $myQuiz;

    private Quiz $theirQuiz;

    private Grade $grade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grade = Grade::factory()->level(3)->create();
        $chapter = Chapter::factory()->create(['grade_id' => $this->grade->id]);

        $this->me = User::factory()->teacher()->create(['name' => 'Cikgu Saya']);
        $this->otherTeacher = User::factory()->teacher()->create(['name' => 'Cikgu Lain']);

        $this->myQuiz = Quiz::factory()->for($chapter)->create([
            'teacher_id' => $this->me->id, 'title' => 'Kuiz Saya', 'type' => Quiz::TYPE_INTERACTIVE,
        ]);
        $this->theirQuiz = Quiz::factory()->for($chapter)->create([
            'teacher_id' => $this->otherTeacher->id, 'title' => 'Kuiz Lain', 'type' => Quiz::TYPE_INTERACTIVE,
        ]);
    }

    private function student(string $name): User
    {
        return User::factory()->student($this->grade->level)->create([
            'name' => $name, 'email' => strtolower($name).'@moe.edu.my',
        ]);
    }

    private function attempt(Quiz $quiz, User $student, int $score): void
    {
        QuizAttempt::factory()->for($quiz)->passed()->create([
            'student_id' => $student->id, 'score' => $score, 'counts_for_ranking' => true,
        ]);
    }

    public function test_only_students_who_answered_this_teachers_quizzes_are_listed(): void
    {
        $mine = $this->student('Murid');
        $stranger = $this->student('Asing');

        $this->attempt($this->myQuiz, $mine, 50);
        // Higher score, but on someone else's quiz — must not appear at all.
        $this->attempt($this->theirQuiz, $stranger, 100);

        $rows = $this->actingAs($this->me)->get(route('cikgu.ranking'))->assertOk()->viewData('rows');
        $names = collect($rows->items())->pluck('student.name')->all();

        $this->assertSame(['Murid'], $names);
        $this->assertSame(1, $rows->total());
    }

    /** A student's points must count only what they scored on this teacher's quizzes. */
    public function test_points_exclude_work_done_on_another_teachers_quiz(): void
    {
        $student = $this->student('Murid');
        $this->attempt($this->myQuiz, $student, 40);
        $this->attempt($this->theirQuiz, $student, 60);

        $rows = $this->actingAs($this->me)->get(route('cikgu.ranking'))->viewData('rows');

        $this->assertSame(40, $rows->items()[0]->points);
        $this->assertSame(1, $rows->items()[0]->quizzes);
    }

    public function test_the_quiz_filter_offers_only_this_teachers_quizzes(): void
    {
        $quizzes = $this->actingAs($this->me)->get(route('cikgu.ranking'))->viewData('quizzes');

        $this->assertSame(['Kuiz Saya'], $quizzes->pluck('title')->all());
    }

    /** The students' own board is platform-wide and must be untouched by this. */
    public function test_the_student_facing_board_still_covers_every_quiz(): void
    {
        $a = $this->student('Satu');
        $b = $this->student('Dua');
        $this->attempt($this->myQuiz, $a, 50);
        $this->attempt($this->theirQuiz, $b, 100);

        $all = app(LeaderboardService::class)->ranking();

        $this->assertEqualsCanonicalizing(['Satu', 'Dua'], $all->pluck('student.name')->all());
    }
}
