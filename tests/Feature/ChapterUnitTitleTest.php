<?php

namespace Tests\Feature;

use App\Models\Chapter;
use Database\Seeders\ChapterUnitTitleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChapterUnitTitleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_placeholder_title_becomes_unit(): void
    {
        $chapter = Chapter::factory()->create(['number' => 3, 'title' => 'Bab 3']);

        $this->seed(ChapterUnitTitleSeeder::class);

        $this->assertSame('Unit 3', $chapter->fresh()->title);
    }

    /** A chapter someone has actually named keeps its title. */
    public function test_a_real_title_is_left_alone(): void
    {
        $named = Chapter::factory()->create(['number' => 1, 'title' => 'Sains Hayat: Manusia']);

        $this->seed(ChapterUnitTitleSeeder::class);

        $this->assertSame('Sains Hayat: Manusia', $named->fresh()->title);
    }

    /** Matched against the row's own number, so "Bab 5" on chapter 2 is not a placeholder. */
    public function test_a_title_naming_a_different_chapter_is_left_alone(): void
    {
        $odd = Chapter::factory()->create(['number' => 2, 'title' => 'Bab 5']);

        $this->seed(ChapterUnitTitleSeeder::class);

        $this->assertSame('Bab 5', $odd->fresh()->title);
    }

    public function test_running_it_twice_changes_nothing_the_second_time(): void
    {
        Chapter::factory()->create(['number' => 1, 'title' => 'Bab 1']);
        Chapter::factory()->create(['number' => 2, 'title' => 'Bab 2']);

        $this->seed(ChapterUnitTitleSeeder::class);
        $after = Chapter::orderBy('id')->pluck('title', 'id');

        $this->seed(ChapterUnitTitleSeeder::class);

        $this->assertEquals($after, Chapter::orderBy('id')->pluck('title', 'id'));
    }
}
