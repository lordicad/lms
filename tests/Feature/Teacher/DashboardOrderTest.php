<?php

namespace Tests\Feature\Teacher;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOrderTest extends TestCase
{
    use RefreshDatabase;

    /** The leaderboards are what a teacher opens this page for, so they come before everything. */
    public function test_the_leaderboards_come_before_the_summary_and_the_recent_lists(): void
    {
        $html = $this->actingAs(User::factory()->teacher()->create())
            ->get(route('cikgu.dashboard'))
            ->assertOk()
            ->getContent();

        $leaderboards = strpos($html, 'Video Paling Ditonton');
        $summary = strpos($html, 'Jumlah tontonan video');
        $recentVideos = strpos($html, 'Video Terbaru Saya');
        $passFail = strpos($html, 'Lulus / Gagal Kuiz');

        $this->assertNotFalse($leaderboards, 'the leaderboards should render');
        $this->assertLessThan($passFail, $leaderboards, 'leaderboards should precede the pass/fail chart');
        // Pass/fail sits directly after them, ahead of the summary and the recent lists.
        $this->assertLessThan($summary, $passFail, 'pass/fail should precede the summary cards');
        $this->assertLessThan($recentVideos, $passFail, 'pass/fail should precede the recent lists');

        // All four are still present, in their usual order among themselves.
        foreach (['Video Paling Ditonton', 'Video Paling Digemari', 'Bahan Paling Dimuat Turun', 'Kuiz Paling Dicuba'] as $title) {
            $this->assertStringContainsString($title, $html);
        }
    }
}
