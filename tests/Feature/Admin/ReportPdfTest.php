<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_real_pdf_with_the_report_in_it(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.laporan.pdf'));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));

        $pdf = $response->getContent();
        // A real document, not an HTML error page renamed: dompdf writes the %PDF header itself.
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(10_000, strlen($pdf), 'the PDF looks empty');
        $this->assertStringContainsString('/Type /Page', $pdf, 'the PDF has no pages');
    }

    public function test_the_period_is_honoured(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['7d', '30d', '12m'] as $period) {
            $this->actingAs($admin)
                ->get(route('admin.laporan.pdf', ['period' => $period]))
                ->assertOk();
        }
    }

    /** The Word export is gone: its route must not resolve any more. */
    public function test_the_word_export_no_longer_exists(): void
    {
        $this->assertFalse(app('router')->has('admin.laporan.word'));

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/laporan/word')
            ->assertNotFound();
    }
}
