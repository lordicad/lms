<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Real chapter titles for Bahasa Melayu, Tahun 2.
 *
 * Chapters are shown as "Bab :number: :title", so this replaces the placeholder titles
 * ("Unit 1", …) with the syllabus units. Matched by (subject, Tahun, number) so it targets
 * exactly this pair and nothing else; run once on deploy. Chapters beyond these eight (if any
 * teacher added them) are left untouched — content is never disturbed here.
 */
return new class extends Migration
{
    /** Bab number => title. */
    private const TITLES = [
        1 => 'Kekeluargaan',
        2 => 'Perpaduan',
        3 => 'Kebersihan dan Kesihatan',
        4 => 'Kebudayaan Kita',
        5 => 'Jati Diri, Patriotisme dan Kewarganegaraan',
        6 => 'Alam Sekitar dan Teknologi Hijau',
        7 => 'Pertanian dan Perternakan',
        8 => 'Integriti',
    ];

    public function up(): void
    {
        $subjectId = DB::table('subjects')->where('slug', 'bahasa-melayu')->value('id');
        $gradeId = DB::table('grades')->where('level', 2)->value('id');

        // Fresh/partial databases (e.g. the test schema before any seeding) simply have nothing
        // to rename yet.
        if (! $subjectId || ! $gradeId) {
            return;
        }

        $now = now();

        foreach (self::TITLES as $number => $title) {
            $key = ['subject_id' => $subjectId, 'grade_id' => $gradeId, 'number' => $number];

            if (DB::table('chapters')->where($key)->exists()) {
                DB::table('chapters')->where($key)->update([
                    'title' => $title,
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('chapters')->insert($key + [
                    'title' => $title,
                    'description' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // A data migration: the previous placeholder titles carry no information worth restoring.
    }
};
