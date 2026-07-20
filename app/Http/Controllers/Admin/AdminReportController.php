<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Server-side Admin dashboard reports (brief §4.6). Both formats render the same data the Home page
 * shows for the selected Platform Activity period. Read-only: they never touch analytics counters.
 */
class AdminReportController extends Controller
{
    public function pdf(Request $request, AdminReportService $report): Response
    {
        $data = $this->reportData($request, $report);

        $pdf = Pdf::loadView('admin.reports.dashboard', $data)->setPaper('a4');

        return $pdf->download($this->filename($data['period'], 'pdf'));
    }

    public function word(Request $request, AdminReportService $report): StreamedResponse
    {
        $data = $this->reportData($request, $report);

        // Render the same report view to HTML, then convert to a real .docx with semantic headings
        // and tables — never an HTML/text file renamed to .docx.
        $html = view('admin.reports.dashboard', array_merge($data, ['forWord' => true]))->render();

        $word = new PhpWord;
        $section = $word->addSection();
        Html::addHtml($section, $html, false, false);

        $filename = $this->filename($data['period'], 'docx');

        return response()->streamDownload(function () use ($word) {
            $word->save('php://output', 'Word2007');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
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
            'registrations' => $report->recentRegistrationsQuery()->get(),
            'pending' => $report->pending(),
        ];
    }

    private function filename(string $period, string $extension): string
    {
        return 'laporan-welearn-'.$period.'-'.Carbon::now()->format('Ymd-His').'.'.$extension;
    }
}
