<?php

namespace Tests\Feature\Teacher;

use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChapterFilterTest extends TestCase
{
    use RefreshDatabase;

    private function offer(Subject $subject, Grade $grade): void
    {
        DB::table('grade_subject')->insertOrIgnore(['subject_id' => $subject->id, 'grade_id' => $grade->id]);
    }

    public function test_the_subject_list_holds_only_subjects_that_year_offers(): void
    {
        $year1 = Grade::factory()->level(1)->create();
        $offered = Subject::factory()->create(['name' => 'Bahasa Melayu Tahun Satu']);
        $notOffered = Subject::factory()->create(['name' => 'Sains Tahun Enam Sahaja']);
        $this->offer($offered, $year1);

        $response = $this->actingAs(User::factory()->teacher()->create())
            ->get(route('cikgu.bab.index', ['tahun' => 1]));

        $response->assertOk();
        $response->assertSee('Bahasa Melayu Tahun Satu');
        $response->assertDontSee('Sains Tahun Enam Sahaja');
    }

    /** The default must be a subject the year actually teaches, not simply the first in the list. */
    public function test_it_defaults_to_a_subject_the_year_offers(): void
    {
        $year6 = Grade::factory()->level(6)->create();
        Subject::factory()->create(['name' => 'Alpha', 'sort_order' => 1]);   // offered nowhere
        $real = Subject::factory()->create(['name' => 'Beta', 'sort_order' => 2]);
        $this->offer($real, $year6);

        $filter = $this->actingAs(User::factory()->teacher()->create())
            ->get(route('cikgu.bab.index', ['tahun' => 6]))
            ->assertOk()
            ->viewData('filter');

        $this->assertSame($real->id, $filter->subject->id);
        $this->assertSame($year6->id, $filter->grade->id);
    }

    /** An old pair stays reachable by URL so the page can still explain it is no longer offered. */
    public function test_a_pair_named_outright_is_kept_even_when_not_offered(): void
    {
        $year1 = Grade::factory()->level(1)->create();
        $stale = Subject::factory()->create(['name' => 'Subjek Lama']);

        $response = $this->actingAs(User::factory()->teacher()->create())
            ->get(route('cikgu.bab.index', ['tahun' => 1, 'subjek' => $stale->slug]));

        $response->assertOk();
        $this->assertSame($stale->id, $response->viewData('filter')->subject->id);
        $this->assertFalse($response->viewData('isOffered'));
        // ...and it still appears in the dropdown, so the control matches the page beneath it.
        $response->assertSee('Subjek Lama');
    }
}
