<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\AdminTalentController;
use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\LessonView;
use App\Models\School;
use App\Models\User;
use App\Services\AdminReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportHardeningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Daily buckets follow Malaysian midnight. 18:00 UTC on the 10th is 02:00 on the 11th in
     * Kuala Lumpur, so that view belongs to the 11th - UTC buckets filed it under the 10th.
     */
    public function test_daily_buckets_use_malaysian_days(): void
    {
        $this->travelTo(Carbon::parse('2026-01-11 20:00:00', 'UTC')); // 04:00 on the 12th in KL

        $school = School::factory()->create();
        $this->actingAs(User::factory()->admin()->atSchool($school)->create());
        $teacher = User::factory()->teacher()->atSchool($school)->create();
        $lesson = Lesson::factory()->for(Chapter::factory())->create(['teacher_id' => $teacher->id]);

        $view = new LessonView(['lesson_id' => $lesson->id, 'student_id' => User::factory()->student(3)->create()->id]);
        $view->created_at = Carbon::parse('2026-01-10 18:00:00', 'UTC');
        $view->save();

        $activity = app(AdminReportService::class)->platformActivity('7d');
        $byLabel = array_combine($activity['labels'], $activity['series']['views']);

        $this->assertSame('12/01', end($activity['labels']), 'today is the 12th in Kuala Lumpur');
        $this->assertSame(1, $byLabel['11/01']);
        $this->assertSame(0, $byLabel['10/01']);
    }

    /**
     * Text that starts with a formula character is exported as plain text, so opening the CSV
     * in Excel or Sheets cannot run it. Numbers - including negatives - stay numbers.
     */
    public function test_csv_cells_cannot_become_spreadsheet_formulas(): void
    {
        $safe = (fn (array $cells) => $this->csvSafe($cells))->call(app(AdminTalentController::class), [
            '=HYPERLINK("http://evil","x")', '+1', '-cmd', '@SUM(A1)', 'Cikgu Ana', -3, 12.5, '',
        ]);

        $this->assertSame([
            "'=HYPERLINK(\"http://evil\",\"x\")", "'+1", "'-cmd", "'@SUM(A1)", 'Cikgu Ana', -3, 12.5, '',
        ], $safe);
    }
}
