<?php

use Database\Seeders\SchoolSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Seeds the reference schools + classes on deploy (deploy.sh runs `migrate --force`, not seeders).
 * Idempotent via the seeder. Skipped in tests, which build exactly the schools they need.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        (new SchoolSeeder)->run();
    }

    public function down(): void
    {
        // Reference data — left in place on rollback. The schools/classes tables are dropped by
        // their own migrations if the whole feature is reverted.
    }
};
