<?php

namespace Tests\Feature\Teacher;

use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * On the teacher's own video list, a video opens a preview in place rather than navigating to the
 * watch page — the same modal the admin content list uses.
 */
class VideoPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function indexAsOwner(Lesson $lesson): string
    {
        return $this->actingAs($lesson->teacher)->get(route('cikgu.video.index'))->assertOk()->getContent();
    }

    public function test_the_list_opens_a_preview_instead_of_the_watch_page(): void
    {
        $lesson = Lesson::factory()->for(Chapter::factory()->create())->create([
            'teacher_id' => User::factory()->teacher()->create()->id,
            'title' => 'Kaedah Pecahan',
            'source' => Lesson::SOURCE_UPLOAD,
        ]);

        $html = $this->indexAsOwner($lesson);

        // The preview modal and its open() trigger are present…
        $this->assertStringContainsString('open(', $html, 'the preview trigger is missing');
        $this->assertStringContainsString("lesson.kind === 'upload'", $html, 'the preview modal is missing');

        // …and the title no longer links to the watch route.
        $this->assertStringNotContainsString(
            route('video.show', $lesson),
            $html,
            'the video still links to the old watch page',
        );
    }

    public function test_a_youtube_video_previews_with_its_embed(): void
    {
        $lesson = Lesson::factory()->for(Chapter::factory()->create())->create([
            'teacher_id' => User::factory()->teacher()->create()->id,
            'source' => Lesson::SOURCE_YOUTUBE,
            'youtube_id' => 'dQw4w9WgXcQ',
        ]);

        $html = $this->indexAsOwner($lesson);

        $this->assertStringContainsString("lesson.kind === 'youtube'", $html);
        $this->assertStringContainsString('youtube', $html);
    }
}
