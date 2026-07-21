<?php

use Database\Seeders\LeaderboardDemoSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Re-seed the demo leaderboard so every Tahun shows a full Top 100. The seeder is idempotent — it
 * upserts its own `rankdemo.*` students and refreshes their attempts — so this just enlarges the
 * existing demo cohort from 12 to 100 per Tahun. Skipped in tests.
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
        // Demo data is left in place on rollback; LeaderboardDemoSeeder::teardown() removes the
        // rankdemo.* accounts if the whole demo is ever cleared.
    }
};
