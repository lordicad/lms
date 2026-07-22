<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * Server-side Admin dashboard report (brief §4.6). Renders the same data the Home page shows for
 * the selected Platform Activity period. Read-only: it never touches analytics counters.
 */
class AdminReportController extends Controller
{
    /** Most recent registrations listed by name; the rest are covered by the total beneath. */
    private const MAX_REGISTRATION_ROWS = 25;

    public function pdf(Request $request, AdminReportService $report): Response
    {
        $data = $this->reportData($request, $report);

        $pdf = Pdf::loadView('admin.reports.dashboard', $data)->setPaper('a4');

        return $pdf->download($this->filename($data['period'], 'pdf'));
    }

    /**
     * Assemble every section shown on Admin Home for the requested, allow-listed period.
     *
     * @return array<string, mixed>
     */
    private function reportData(Request $request, AdminReportService $report): array
    {
        $period = AdminDashboardController::resolvePeriod($request->string('period')->toString());

        return [
            'period' => $period,
            'periodLabel' => AdminReportService::PERIOD_LABELS[$period],
            'generatedAt' => Carbon::now(),
            'timezone' => config('app.timezone'),
            'totals' => $report->totals(),
            'contributors' => $report->contributors()->take(3),
            'topContent' => $report->topContent(),
            'activity' => $report->platformActivity($period),
            // Capped, because it is the one unbounded section: a school that onboarded its whole
            // roll in a week put every account in this table, and rendering hundreds of rows took
            // the PDF over PHP's memory limit. A report wants the recent ones and a total, not a
            // register — the full list is on the Users page.
            'registrations' => $report->recentRegistrationsQuery()->limit(self::MAX_REGISTRATION_ROWS)->get(),
            'registrationsTotal' => $report->recentRegistrationsQuery()->count(),
            'registrationsShown' => self::MAX_REGISTRATION_ROWS,
            'pending' => $report->pending(),
        ];
    }

    private function filename(string $period, string $extension): string
    {
        return 'laporan-welearn-'.$period.'-'.Carbon::now()->format('Ymd-His').'.'.$extension;
    }
}
