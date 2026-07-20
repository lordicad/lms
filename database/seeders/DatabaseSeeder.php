<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            GradeSeeder::class,
            Kurikulum2027Seeder::class,
            SchoolSeeder::class,
        ]);

        // Demo accounts and sample content are for local work only. A production database
        // starts with the taxonomy and nothing else.
        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
