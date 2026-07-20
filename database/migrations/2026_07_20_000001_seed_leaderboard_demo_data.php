<?php

use Database\Seeders\LeaderboardDemoSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Populates the student leaderboard with demo data on deploy (seeders don't run
 * automatically, migrations do). The seeder is self-contained, idempotent and
 * only touches its own `rankdemo.*` accounts; if no quizzes exist yet it skips.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Skip demo leaderboard data in tests; it is only for the deployed demo environment.
        if (app()->environment('testing')) {
            return;
        }

        (new LeaderboardDemoSeeder)->run();
    }

    public function down(): void
    {
        LeaderboardDemoSeeder::teardown();
    }
};
