<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Kept as a thin shim: the Kurikulum 2027 taxonomy is owned entirely by Kurikulum2027Seeder.
 * Edit the subject list in database/seeders/data/kurikulum_2027.php, not here.
 */
class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(Kurikulum2027Seeder::class);
    }
}
