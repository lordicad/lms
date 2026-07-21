<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Demo leaderboard data: puts a full podium + Top 10 on /ranking for every
 * Tahun, matching the design instead of the empty state.
 *
 * Self-contained and safe:
 *  - Creates its OWN demo students (email prefix `rankdemo.`), so it does not
 *    depend on any other seeder having run, and never reads or mutates real
 *    accounts or their attempts.
 *  - Idempotent: students are upserted by unique email and their attempts are
 *    cleared before re-inserting, so re-running just refreshes the demo.
 *  - Needs at least one Quiz to exist (an attempt references a real quiz); if
 *    none exist it logs and skips rather than erroring.
 *
 * Identifiable for teardown: every account it makes has email like
 * `rankdemo.%@moe.edu.my` (see EMAIL_PREFIX / DOMAIN).
 */
class LeaderboardDemoSeeder extends Seeder
{
    /** How many demo students to rank per Tahun. 100+ so the Top 100 leaderboard is full. */
    public const PER_GRADE = 100;

    /** Marks the accounts this seeder owns, so they can be found and removed. */
    public const EMAIL_PREFIX = 'rankdemo.';

    public const DOMAIN = 'moe.edu.my';

    private const POINTS_PER_CORRECT = 20;

    /** Quizzes attempted per rank (top students do more); capped by how many quizzes exist. */
    private const QUIZ_COUNTS = [6, 6, 5, 5, 5, 4, 4, 4, 3, 3, 3, 2];

    /** Deterministic name pools — a distinct pupil per (grade, rank). */
    private const FIRST = [
        'Adam', 'Aisyah', 'Harith', 'Meiling', 'Danial', 'Nurul', 'Iman', 'Aqil', 'Sofea', 'Hakim',
        'Balqis', 'Zikri', 'Alia', 'Irfan', 'Hana', 'Amir', 'Qistina', 'Ryan', 'Chloe', 'Weijie',
        'Arjun', 'Divya', 'Kavya', 'Ravi', 'Xinyi', 'Ashwin', 'Farah', 'Aina', 'Yusuf', 'Zara',
    ];

    private const LAST = [
        'Yusof', 'Abdullah', 'Rahman', 'Hashim', 'Tan', 'Lim', 'Wong', 'Lee', 'Raj', 'Kumar',
        'Nair', 'Singh', 'Ismail', 'Bakar', 'Osman', 'Aziz', 'Ibrahim', 'Halim', 'Rashid', 'Idris',
    ];

    public function run(): void
    {
        $quizIds = Quiz::query()->orderBy('id')->limit(8)->pluck('id')->all();

        if (empty($quizIds)) {
            $this->command?->warn('LeaderboardDemoSeeder: no quizzes exist yet — nothing to attach attempts to. Skipped.');

            return;
        }

        $quizCount = count($quizIds);
        $now = now();
        $password = Hash::make('Student@123');
        $totalAttempts = 0;

        foreach (Grade::orderBy('level')->get() as $grade) {
            // 1) Ensure this grade's demo students exist (upsert by unique email).
            $userRows = [];
            for ($i = 0; $i < self::PER_GRADE; $i++) {
                $k = ($grade->level - 1) * self::PER_GRADE + $i;
                $first = self::FIRST[$k % count(self::FIRST)];
                $last = self::LAST[intdiv($k, count(self::FIRST)) % count(self::LAST)];
                $handle = self::EMAIL_PREFIX."{$grade->level}.{$i}";

                $userRows[] = [
                    'name' => "{$first} {$last}",
                    'username' => $handle,
                    'email' => "{$handle}@".self::DOMAIN,
                    'password' => $password,
                    'role' => User::ROLE_STUDENT,
                    'is_active' => true,
                    'grade_id' => $grade->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('users')->upsert($userRows, ['email'], ['name', 'grade_id', 'is_active']);

            $students = User::query()
                ->where('email', 'like', self::EMAIL_PREFIX.$grade->level.'.%@'.self::DOMAIN)
                ->orderBy('id')
                ->get()
                ->sortBy(fn ($u) => (int) explode('.', $u->username)[2])   // rank order 0..11
                ->values();

            // 2) Refresh their attempts.
            DB::table('quiz_attempts')->whereIn('student_id', $students->pluck('id'))->delete();

            $rows = [];
            foreach ($students as $i => $student) {
                $accuracyPct = max(45, 95 - intdiv($i * 2, 3));            // gentle decline across 100 ranks
                $attempts = min($quizCount, self::QUIZ_COUNTS[$i] ?? 2);
                $correctTotal = (int) round($accuracyPct / 100 * $attempts * 10);
                $tieBreakBonus = self::PER_GRADE - $i;                     // keeps points strictly descending

                for ($a = 0; $a < $attempts; $a++) {
                    $correct = min(10, intdiv($correctTotal, $attempts) + ($a < $correctTotal % $attempts ? 1 : 0));
                    $completedAt = $now->copy()->subDays($i)->subMinutes($a * 30 + $i);

                    $rows[] = [
                        'quiz_id' => $quizIds[$a % $quizCount],            // distinct quiz per attempt
                        'student_id' => $student->id,
                        'score' => $correct * self::POINTS_PER_CORRECT + ($a === 0 ? $tieBreakBonus : 0),
                        'max_score' => 10 * self::POINTS_PER_CORRECT,
                        'correct_count' => $correct,
                        'question_count' => 10,
                        'counts_for_ranking' => true,
                        'started_at' => $completedAt->copy()->subMinutes(8),
                        'completed_at' => $completedAt,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            DB::table('quiz_attempts')->insert($rows);
            $totalAttempts += count($rows);
            $this->command?->info("LeaderboardDemoSeeder: {$grade->name} — ranked {$students->count()} students.");
        }

        $this->command?->info("LeaderboardDemoSeeder: done, {$totalAttempts} demo attempts inserted.");
    }

    /** Remove everything this seeder created (used by the migration's down()). */
    public static function teardown(): void
    {
        $ids = User::query()
            ->where('email', 'like', self::EMAIL_PREFIX.'%@'.self::DOMAIN)
            ->pluck('id');

        DB::table('quiz_attempts')->whereIn('student_id', $ids)->delete();
        DB::table('users')->whereIn('id', $ids)->delete();
    }
}
