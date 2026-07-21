<?php

use Database\Seeders\LeaderboardDemoSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Refresh the demo leaderboard attempts with the corrected scoring: scores out of 100, varied per
 * student and attempt, each with a dummy duration. Idempotent — the seeder clears and re-inserts
 * only its own rankdemo.* students' attempts. Skipped in tests.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        (new LeaderboardDemoSeeder)->run();
    }

    public function down(): void
    {
        // Demo data only; left in place on rollback.
    }
};
