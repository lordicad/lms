<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminReportService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * The admin landing page — a calm, read-only overview of the whole platform. Every number is a real
 * aggregate computed by AdminReportService, which the PDF/DOCX exports reuse so the reports match.
 */
class AdminDashboardController extends Controller
{
    public function __invoke(Request $request, AdminReportService $report): View
    {
        $period = $this->resolvePeriod($request->string('period')->toString());

        return view('admin.dashboard', [
            'period' => $period,
            'topContributors' => $report->contributors()->take(3),
            'topContent' => $report->topContent(),
            'activity' => $report->platformActivity($period),
            'registrations' => $report->recentRegistrationsQuery()->take(10)->get(),
            'registrationsCount' => $report->recentRegistrationsQuery()->count(),
            'pending' => $report->pending(),
            'totals' => $report->totals(),
        ]);
    }

    /**
     * The complete contributor ranking (brief §4.2), paginated. Uses the same transparent metric and
     * deterministic tie-break as the Home podium.
     */
    public function contributors(Request $request, AdminReportService $report): View
    {
        $all = $report->contributors();

        $perPage = 25;
        $page = max(1, (int) $request->integer('page', 1));
        $items = $all->forPage($page, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('admin.penyumbang', ['contributors' => $paginator]);
    }

    public static function resolvePeriod(string $period): string
    {
        return in_array($period, AdminReportService::PERIODS, true) ? $period : '7d';
    }
}
