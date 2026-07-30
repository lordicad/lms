<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Real chapter titles for Matematik Tahun 1 (KSSR), replacing the generic "Unit N" placeholders.
 *
 * Only a chapter still carrying its placeholder title ("Unit N" or the older "Bab N") is renamed —
 * matched against the row's own number — so a chapter a teacher has already retitled keeps its
 * title, and re-running changes nothing.
 */
return new class extends Migration
{
    private const TITLES = [
        1 => 'Nombor Hingga 100',
        2 => 'Tambah dan Tolak',
        3 => 'Pecahan',
        4 => 'Wang',
        5 => 'Masa dan Waktu',
        6 => 'Panjang, Jisim dan Isi Padu Cecair',
        7 => 'Bentuk',
        8 => 'Data',
    ];

    public function up(): void
    {
        $subjectId = DB::table('subjects')->where('name', 'Matematik')->value('id');
        $gradeId = DB::table('grades')->where('level', 1)->value('id');

        if (! $subjectId || ! $gradeId) {
            return;
        }

        foreach (self::TITLES as $number => $title) {
            DB::table('chapters')
                ->where('subject_id', $subjectId)
                ->where('grade_id', $gradeId)
                ->where('number', $number)
                ->whereIn('title', ["Unit {$number}", "Bab {$number}"])
                ->update(['title' => $title]);
        }
    }

    public function down(): void
    {
        $subjectId = DB::table('subjects')->where('name', 'Matematik')->value('id');
        $gradeId = DB::table('grades')->where('level', 1)->value('id');

        if (! $subjectId || ! $gradeId) {
            return;
        }

        // Reverse only the exact titles this migration set, back to the "Unit N" placeholder.
        foreach (self::TITLES as $number => $title) {
            DB::table('chapters')
                ->where('subject_id', $subjectId)
                ->where('grade_id', $gradeId)
                ->where('number', $number)
                ->where('title', $title)
                ->update(['title' => "Unit {$number}"]);
        }
    }
};
