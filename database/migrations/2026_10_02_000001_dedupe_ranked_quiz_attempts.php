<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Repairs leaderboard points doubled by the quiz double-start race (fixed in b5943bd).
 *
 * Before that fix two simultaneous starts could each create a ranked attempt for the same student
 * and quiz, and the leaderboard sums every ranked attempt - so that quiz's points counted twice
 * (and perfect-score badges too). The rule has always been "the first completed attempt is the
 * ranked one; everything after it is practice", so for each (student, quiz) with more than one
 * ranked attempt this keeps the earliest completed one ranked and marks the rest as practice.
 *
 * Nothing is deleted - the extra attempts stay, with their scores and answers, as practice - and
 * every attempt changed is logged. On a database with no duplicates this does nothing. Leaderboard
 * points and badges are computed live from attempts, so they correct themselves immediately.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pairs = DB::table('quiz_attempts')
            ->where('counts_for_ranking', true)
            ->select('student_id', 'quiz_id')
            ->groupBy('student_id', 'quiz_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($pairs as $pair) {
            DB::transaction(function () use ($pair) {
                // Earliest completed first; an attempt never finished only wins if none were.
                $ids = DB::table('quiz_attempts')
                    ->where('student_id', $pair->student_id)
                    ->where('quiz_id', $pair->quiz_id)
                    ->where('counts_for_ranking', true)
                    ->orderByRaw('completed_at IS NULL')
                    ->orderBy('completed_at')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->pluck('id');

                $kept = $ids->shift();

                DB::table('quiz_attempts')->whereIn('id', $ids)->update(['counts_for_ranking' => false]);

                Log::info('dedupe_ranked_quiz_attempts: duplicate ranked attempts made practice', [
                    'student_id' => $pair->student_id,
                    'quiz_id' => $pair->quiz_id,
                    'kept_ranked_attempt' => $kept,
                    'now_practice_attempts' => $ids->all(),
                ]);
            });
        }
    }

    /**
     * A data repair: re-creating the duplicates would only re-break the leaderboard. The attempts
     * affected are in the log if they ever need inspecting.
     */
    public function down(): void
    {
        //
    }
};
