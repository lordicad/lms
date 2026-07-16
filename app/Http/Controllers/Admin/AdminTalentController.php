<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use App\Services\TalentService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * MOE oversight — the teacher talent dashboard. Read-only. A "shortlist for review", not a raw
 * leaderboard: the framing (and the prominent disclaimer in the view) keep this a signal, not a
 * verdict. Admins get no content-authoring tools.
 */
class AdminTalentController extends Controller
{
    public function index(Request $request, TalentService $talent): View
    {
        $cohort = $talent->cohort();

        $subjectSlug = $request->string('subjek')->toString() ?: null;
        $gradeLevel = $request->integer('tahun') ?: null;
        $shortlist = $request->boolean('shortlist');

        // Filters narrow to teachers who have a counted lesson in the chosen subject / grade.
        if ($subjectSlug) {
            $cohort = $cohort->filter(fn ($row) => $row->lessons->contains(
                fn ($entry) => $entry->lesson->chapter->subject->slug === $subjectSlug,
            ));
        }

        if ($gradeLevel) {
            $cohort = $cohort->filter(fn ($row) => $row->lessons->contains(
                fn ($entry) => $entry->lesson->chapter->grade->level === $gradeLevel,
            ));
        }

        // Shortlist surfaces quality + outcome over sheer reach, so a big cohort can't dominate,
        // and hides teachers without enough data to judge.
        if ($shortlist) {
            $cohort = $cohort
                ->filter(fn ($row) => $row->sufficient)
                ->sortByDesc(fn ($row) => $row->norm['quality'] + ($row->norm['outcome'] ?? 0));
        }

        return view('admin.bakat', [
            'cohort' => $cohort->values(),
            'subjects' => Subject::orderBy('sort_order')->get(),
            'grades' => Grade::orderBy('level')->get(),
            'subjectSlug' => $subjectSlug,
            'gradeLevel' => $gradeLevel,
            'shortlist' => $shortlist,
        ]);
    }

    public function show(User $teacher, TalentService $talent): View
    {
        abort_unless($teacher->isTeacher(), 404);

        return view('admin.bakat-show', [
            'result' => $talent->forTeacher($teacher),
        ]);
    }

    /** A plain streamed CSV — no server-side spreadsheet libraries on shared hosting. */
    public function export(TalentService $talent): StreamedResponse
    {
        $cohort = $talent->cohort();

        return response()->streamDownload(function () use ($cohort) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Guru', 'Emel', 'Skor Bakat', 'Engagement', 'Quality (%)', 'Breadth (bab)',
                'Outcome', 'Murid terlibat', 'Channel disambung', 'Data mencukupi',
            ]);

            foreach ($cohort as $row) {
                fputcsv($out, [
                    $row->teacher->name,
                    $row->teacher->email,
                    $row->headline ?? '',
                    round($row->raw['engagement']),
                    round($row->raw['quality'] * 100, 1),
                    $row->raw['breadth'],
                    $row->raw['outcome'] ?? '',
                    $row->engaged_students,
                    $row->channels,
                    $row->sufficient ? 'Ya' : 'Tidak',
                ]);
            }

            fclose($out);
        }, 'skor-bakat-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
