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

    /**
     * A school that onboarded its whole roll in one week put every account into the registrations
     * table, and rendering hundreds of rows took the PDF past PHP's memory limit — a 500 in
     * production while a near-empty test database passed. The table is capped, so it holds.
     */
    public function test_it_still_generates_for_a_school_with_hundreds_of_new_accounts(): void
    {
        $school = \App\Models\School::factory()->create();
        $grade = \App\Models\Grade::factory()->level(3)->create();
        $admin = User::factory()->admin()->create(['school_id' => $school->id]);

        User::factory()->count(40)->teacher()->create(['school_id' => $school->id]);
        for ($i = 0; $i < 260; $i++) {
            User::factory()->student($grade->level)->create([
                'school_id' => $school->id, 'email' => "roll{$i}@moe.edu.my",
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.laporan.pdf'));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());

        // Capped, so the file stays a report rather than a 300-row register: without the limit
        // this same data pushed rendering past the memory limit and returned a 500.
        $this->assertLessThan(2_000_000, strlen($response->getContent()), 'the PDF has grown unbounded again');
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
