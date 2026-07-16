<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Kept as a thin shim: starter chapters are created by Kurikulum2027Seeder, which only fills
 * offered (subject, Tahun) pairs. Chapter titles are generic; teachers rename them from /cikgu/bab.
 */
class ChapterSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(Kurikulum2027Seeder::class);
    }
}
