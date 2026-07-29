<?php

namespace Tests\Feature\Student;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizBadgeTest extends TestCase
{
    use RefreshDatabase;

    private function attempt(Quiz $quiz, User $student, int $score, bool $ranked = false, ?string $when = null): QuizAttempt
    {
        return QuizAttempt::factory()->for($quiz)->create([
            'student_id' => $student->id,
            'score' => $score,
            'max_score' => 100,
            'counts_for_ranking' => $ranked,
            'completed_at' => $when ? now()->parse($when) : now(),
        ]);
    }

    public function test_a_low_first_attempt_earns_only_the_completion_badge(): void
    {
        $quiz = Quiz::factory()->create();
        $student = User::factory()->student()->create();
        $a1 = $this->attempt($quiz, $student, 33, ranked: true);

        $result = app(BadgeService::class)->forAttempt($a1);

        $this->assertEqualsCanonicalizing(['completed'], $result['earned']);
        $this->assertEqualsCanonicalizing(['completed'], $result['new']);
    }

    public function test_tier_badges_follow_the_first_attempt_score(): void
    {
        $service = app(BadgeService::class);

        foreach ([75 => 'bronze', 85 => 'silver', 93 => 'gold', 100 => 'perfect'] as $score => $tier) {
            $quiz = Quiz::factory()->create();
            $student = User::factory()->student()->create();
            $a1 = $this->attempt($quiz, $student, $score, ranked: true);

            $this->assertEqualsCanonicalizing(['completed', $tier], $service->forAttempt($a1)['earned']);
        }
    }

    public function test_a_better_practice_retry_does_not_upgrade_the_tier(): void
    {
        $quiz = Quiz::factory()->create();
        $student = User::factory()->student()->create();
        $this->attempt($quiz, $student, 75, ranked: true, when: '-20 minutes');
        $a2 = $this->attempt($quiz, $student, 100, when: '-10 minutes');

        $earned = app(BadgeService::class)->forAttempt($a2)['earned'];

        $this->assertContains('bronze', $earned);
        $this->assertNotContains('perfect', $earned);
    }

    public function test_three_attempts_earns_never_give_up_and_a_pass_after_a_fail_earns_comeback(): void
    {
        $quiz = Quiz::factory()->create();
        $student = User::factory()->student()->create();
        $this->attempt($quiz, $student, 40, ranked: true, when: '-30 minutes');
        $this->attempt($quiz, $student, 50, when: '-20 minutes');
        $a3 = $this->attempt($quiz, $student, 90, when: '-10 minutes');

        $result = app(BadgeService::class)->forAttempt($a3);

        // First attempt failed (40%), so no tier — just completion, plus the two add-ons.
        $this->assertEqualsCanonicalizing(['completed', 'never_give_up', 'comeback'], $result['earned']);
        // completion was earned on attempt 1; the add-ons are what's new on attempt 3.
        $this->assertEqualsCanonicalizing(['never_give_up', 'comeback'], $result['new']);
    }

    public function test_the_profile_collection_counts_each_badge_once_per_quiz(): void
    {
        $student = User::factory()->student()->create();

        $quizA = Quiz::factory()->create();
        $this->attempt($quizA, $student, 93, ranked: true);

        $quizB = Quiz::factory()->create();
        $this->attempt($quizB, $student, 100, ranked: true);

        $counts = app(BadgeService::class)->collectionFor($student);

        $this->assertSame(2, $counts['completed']);
        $this->assertSame(1, $counts['gold']);
        $this->assertSame(1, $counts['perfect']);
        $this->assertSame(0, $counts['never_give_up']);
    }
}
