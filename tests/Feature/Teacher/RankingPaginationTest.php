<?php

namespace Tests\Feature\Teacher;

use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingPaginationTest extends TestCase
{
    use RefreshDatabase;

    /** Ranked students, each scoring a little less than the one before so the order is fixed. */
    private function seedRankedStudents(int $count): Quiz
    {
        $chapter = Chapter::factory()->create();
        $quiz = Quiz::factory()->for($chapter)->create();

        for ($i = 0; $i < $count; $i++) {
            $student = User::factory()->student(3)->create(['email' => "murid{$i}@moe.gov.my"]);
            QuizAttempt::factory()->for($quiz)->passed()->create([
                'student_id' => $student->id,
                'score' => 100 - $i,
                'counts_for_ranking' => true,
            ]);
        }

        return $quiz;
    }

    public function test_the_first_page_holds_fifty_students(): void
    {
        $this->seedRankedStudents(60);

        $rows = $this->actingAs(User::factory()->teacher()->create())
            ->get(route('cikgu.ranking'))
            ->assertOk()
            ->viewData('rows');

        $this->assertCount(50, $rows->items());
        $this->assertSame(60, $rows->total());
        $this->assertSame(1, $rows->first()->rank);
        $this->assertSame(50, $rows->last()->rank);
    }

    /** The point of the change: page two carries on at 51, it does not restart at 1. */
    public function test_numbering_continues_on_the_second_page(): void
    {
        $this->seedRankedStudents(60);

        $rows = $this->actingAs(User::factory()->teacher()->create())
            ->get(route('cikgu.ranking', ['page' => 2]))
            ->assertOk()
            ->viewData('rows');

        $this->assertCount(10, $rows->items());
        $this->assertSame(51, $rows->first()->rank);
        $this->assertSame(60, $rows->last()->rank);
    }

    public function test_paging_keeps_the_active_filter(): void
    {
        $this->seedRankedStudents(60);

        $html = $this->actingAs(User::factory()->teacher()->create())
            ->get(route('cikgu.ranking', ['tahun' => 3]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('tahun=3', $html);
    }
}
