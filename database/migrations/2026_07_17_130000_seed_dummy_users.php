<?php

use Database\Seeders\DummyUsersSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the demo cohort — 189 teachers, 600 students — through a migration, because that is the only
 * hook the deploy pipeline runs automatically (deploy.sh runs `migrate --force`, not seeders).
 *
 * The generation lives in DummyUsersSeeder; this is just the trigger. Reversible: down() removes
 * exactly the accounts these two domains identify, and nothing else.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new DummyUsersSeeder)->run();
    }

    public function down(): void
    {
        // Delete exactly the generated emails, never a pattern: admin@moe.gov.my is a real account
        // on the same domain, and a LIKE '%@moe.gov.my' would take it out too.
        $rows = DummyUsersSeeder::rows();
        $emails = array_merge(
            array_column($rows['teachers'], 'email'),
            array_column($rows['students'], 'email'),
        );

        foreach (array_chunk($emails, 500) as $chunk) {
            DB::table('users')->whereIn('email', $chunk)->delete();
        }
    }
};
