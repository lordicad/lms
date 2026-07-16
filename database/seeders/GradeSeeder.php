<?php

namespace Database\Seeders;

use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 6) as $level) {
            Grade::updateOrCreate(
                ['level' => $level],
                ['name' => "Tahun {$level}"],
            );
        }
    }
}
