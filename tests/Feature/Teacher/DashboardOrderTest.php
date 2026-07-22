<?php

namespace Tests\Feature\Teacher;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOrderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The running order of the dashboard, pinned.
     *
     * It reads summary, then leaderboards, then the recent lists. An earlier arrangement put the
     * leaderboards above everything and the summary last; the two-column design replaced it, and
     * pass/fail moved into the side column rather than sitting between them.
     */
    public function test_the_summary_leads_and_the_leaderboards_precede_the_recent_lists(): void
    {
        $html = $this->page();

        $summary = strpos($html, 'Jumlah tontonan video');
        $leaderboards = strpos($html, 'Video Paling Ditonton');
        $recentVideos = strpos($html, 'Video Terbaru Saya');

        $this->assertNotFalse($summary, 'the summary cards should render');
        $this->assertNotFalse($leaderboards, 'the leaderboards should render');

        $this->assertLessThan($leaderboards, $summary, 'the summary cards lead');
        $this->assertLessThan($recentVideos, $leaderboards, 'leaderboards should precede the recent lists');
    }

    /** Rearranging must not drop a panel. Every one of them is still on the page. */
    public function test_nothing_was_lost_in_the_rearrangement(): void
    {
        $html = $this->page();

        foreach ([
            'Video Paling Ditonton',
            'Video Paling Digemari',
            'Bahan Paling Dimuat Turun',
            'Kuiz Paling Dicuba',
            'Lulus / Gagal Kuiz',
            'Video Terbaru Saya',
            'Kuiz Saya',
            'Jumlah tontonan video',
            'Video digemari',
            'Bahan dimuat turun',
            'Percubaan kuiz',
        ] as $panel) {
            $this->assertStringContainsString($panel, $html, "the dashboard lost: {$panel}");
        }
    }

    /**
     * Every quick action points somewhere real.
     *
     * They are hand-written route names in a Blade array, so a renamed route would only surface as
     * a 500 the first time a teacher opened the page.
     */
    public function test_the_quick_actions_all_resolve(): void
    {
        $html = $this->page();

        foreach (['cikgu.video.create', 'cikgu.bahan.create', 'cikgu.kuiz.create', 'cikgu.bab.index'] as $name) {
            $this->assertStringContainsString(route($name), $html, "quick action {$name} is missing");
        }
    }

    private function page(): string
    {
        return $this->actingAs(User::factory()->teacher()->create())
            ->get(route('cikgu.dashboard'))
            ->assertOk()
            ->getContent();
    }
}
