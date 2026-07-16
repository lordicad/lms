<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The single writer of the Kurikulum Persekolahan 2027 taxonomy.
 *
 * Idempotent and safe to run on a live database: existing subjects are matched by slug and
 * updated in place (their ids, chapters and content are preserved), availability is synced to
 * the pivot, starter chapters are filled in for offered (subject, Tahun) pairs, and chapters
 * whose pair is no longer offered are either removed (when empty) or deactivated (when they
 * still hold a teacher's content — content is never deleted here).
 *
 * Standalone rerun after editing the master list:
 *   php artisan db:seed --class=Kurikulum2027Seeder --force
 */
class Kurikulum2027Seeder extends Seeder
{
    private const STARTER_CHAPTERS = 8;

    public function run(): void
    {
        DB::transaction(function () {
            $this->upsertSubjects();
            $this->syncPivot();
            $this->seedStarterChapters();
            $this->reconcileInvalidChapters();
        });
    }

    /**
     * The master list, parsed into associative rows. Slug is derived from the full name.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function subjects(): array
    {
        return collect(require __DIR__.'/data/kurikulum_2027.php')
            ->map(fn (array $row) => [
                'bil' => $row[0],
                'name' => $row[1],
                'short_name' => $row[2],
                'category' => $row[3],
                'levels' => $row[4],
                'color' => $row[5],
                'icon' => $row[6],
                'slug' => Str::slug($row[1]),
            ])
            ->all();
    }

    /**
     * Upsert every subject by slug. The original four keep their ids, chapters and content.
     */
    public function upsertSubjects(): void
    {
        foreach (self::subjects() as $subject) {
            Subject::updateOrCreate(
                ['slug' => $subject['slug']],
                [
                    'name' => $subject['name'],
                    'short_name' => $subject['short_name'],
                    'category' => $subject['category'],
                    'color' => $subject['color'],
                    'icon' => $subject['icon'],
                    'sort_order' => $subject['bil'],
                ],
            );
        }
    }

    /**
     * Bring grade_subject to exactly the offered (grade, subject) pairs — 124 of them.
     */
    public function syncPivot(): void
    {
        $gradeIdByLevel = Grade::pluck('id', 'level');
        $subjectIdBySlug = Subject::pluck('id', 'slug');

        $desired = [];

        foreach (self::subjects() as $subject) {
            $subjectId = $subjectIdBySlug[$subject['slug']] ?? null;

            if (! $subjectId) {
                continue;
            }

            foreach ($subject['levels'] as $level) {
                $gradeId = $gradeIdByLevel[$level] ?? null;

                if ($gradeId) {
                    $desired["{$gradeId}:{$subjectId}"] = ['grade_id' => $gradeId, 'subject_id' => $subjectId];
                }
            }
        }

        $existing = DB::table('grade_subject')->get()
            ->mapWithKeys(fn ($row) => ["{$row->grade_id}:{$row->subject_id}" => true])
            ->all();

        if ($insert = array_values(array_diff_key($desired, $existing))) {
            DB::table('grade_subject')->insert($insert);
        }

        foreach (array_keys(array_diff_key($existing, $desired)) as $key) {
            [$gradeId, $subjectId] = explode(':', $key);

            DB::table('grade_subject')
                ->where('grade_id', $gradeId)
                ->where('subject_id', $subjectId)
                ->delete();
        }
    }

    /**
     * Create the starter Bab 1..8 for every offered pair that has no chapters yet. Pairs that
     * already hold chapters (renamed by teachers, or from a previous seed) are left untouched.
     */
    public function seedStarterChapters(): void
    {
        $withChapters = DB::table('chapters')
            ->select('subject_id', 'grade_id')
            ->distinct()
            ->get()
            ->mapWithKeys(fn ($row) => ["{$row->subject_id}:{$row->grade_id}" => true])
            ->all();

        $now = now();
        $rows = [];

        foreach ($this->validPairs() as $key => [$subjectId, $gradeId]) {
            if (isset($withChapters[$key])) {
                continue;
            }

            foreach (range(1, self::STARTER_CHAPTERS) as $number) {
                $rows[] = [
                    'subject_id' => $subjectId,
                    'grade_id' => $gradeId,
                    'number' => $number,
                    'title' => "Bab {$number}",
                    'description' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('chapters')->insert($chunk);
        }
    }

    /**
     * Deactivate or remove chapters whose (subject, Tahun) is no longer offered, and reactivate
     * any that came back into the curriculum. Empty stranded chapters are deleted; those holding
     * content are deactivated so the content survives for the teacher to move.
     */
    public function reconcileInvalidChapters(): void
    {
        $valid = $this->validPairs();

        $chapters = Chapter::withCount(['lessons', 'materials', 'quizzes'])->get();

        $delete = [];
        $deactivate = [];
        $reactivate = [];

        foreach ($chapters as $chapter) {
            $isValid = isset($valid["{$chapter->subject_id}:{$chapter->grade_id}"]);
            $hasContent = ($chapter->lessons_count + $chapter->materials_count + $chapter->quizzes_count) > 0;

            if ($isValid) {
                if (! $chapter->is_active) {
                    $reactivate[] = $chapter->id;
                }
            } elseif (! $hasContent) {
                $delete[] = $chapter->id;
            } elseif ($chapter->is_active) {
                $deactivate[] = $chapter->id;
            }
        }

        if ($delete) {
            Chapter::whereIn('id', $delete)->delete();
        }

        if ($deactivate) {
            Chapter::whereIn('id', $deactivate)->update(['is_active' => false]);
        }

        if ($reactivate) {
            Chapter::whereIn('id', $reactivate)->update(['is_active' => true]);
        }
    }

    /**
     * The offered pairs, keyed "subjectId:gradeId" for O(1) membership tests.
     *
     * @return array<string, array{0:int,1:int}>
     */
    private function validPairs(): array
    {
        $gradeIdByLevel = Grade::pluck('id', 'level');
        $subjectIdBySlug = Subject::pluck('id', 'slug');

        $pairs = [];

        foreach (self::subjects() as $subject) {
            $subjectId = $subjectIdBySlug[$subject['slug']] ?? null;

            if (! $subjectId) {
                continue;
            }

            foreach ($subject['levels'] as $level) {
                $gradeId = $gradeIdByLevel[$level] ?? null;

                if ($gradeId) {
                    $pairs["{$subjectId}:{$gradeId}"] = [$subjectId, $gradeId];
                }
            }
        }

        return $pairs;
    }
}
