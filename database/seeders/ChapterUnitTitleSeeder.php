<?php

namespace Database\Seeders;

use App\Models\Chapter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Renames the placeholder chapter titles from "Bab N" to "Unit N".
 *
 * Chapters are shown as "Bab :number: :title", so a chapter seeded with the title "Bab 1" read as
 * "Bab 1: Bab 1". It now reads "Bab 1: Unit 1".
 *
 * Only a title that merely repeats its own number is touched — matched against the row's `number`
 * rather than a list of names, so a chapter someone has actually titled ("Sains Hayat: Manusia")
 * keeps it. Re-running changes nothing: a renamed row no longer matches.
 */
class ChapterUnitTitleSeeder extends Seeder
{
    public function run(): void
    {
        $renamed = Chapter::whereRaw("title = CONCAT('Bab ', number)")
            ->update(['title' => DB::raw("CONCAT('Unit ', number)")]);

        $this->command?->info("Chapter titles renamed to Unit: {$renamed}");

        $kept = Chapter::whereRaw("title <> CONCAT('Unit ', number)")->count();

        if ($kept > 0) {
            $this->command?->info("Left as they are (a real title, not a placeholder): {$kept}");
        }
    }
}
