<?php

namespace Tests\Feature\Teacher;

use App\Http\Requests\LessonRequest;
use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uploading a batch makes one video per file, each titled by its own row.
 *
 * Titles pair to files by position and thumbnails by explicit key, so the cases worth holding are
 * the ones where either could slip: a blank title mid-list, a gap in the thumbnails.
 */
class VideoMultiUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Chapter $chapter;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('uploads');
        $this->teacher = User::factory()->teacher()->create();
        $this->chapter = Chapter::factory()->create();
    }

    private function video(string $name, int $kb = 400): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kb, 'video/mp4');
    }

    /** @param array<int, UploadedFile> $videos */
    private function submit(array $videos, array $titles = [], array $extra = [])
    {
        return $this->actingAs($this->teacher)->post(route('cikgu.video.store'), array_merge([
            'chapter_id' => $this->chapter->id,
            'source' => Lesson::SOURCE_UPLOAD,
            'videos' => $videos,
            'video_titles' => $titles,
        ], $extra));
    }

    public function test_each_file_becomes_its_own_video(): void
    {
        $this->submit(
            [$this->video('kelas1.mp4'), $this->video('kelas2.mp4'), $this->video('kelas3.mp4')],
            ['Pecahan Asas', 'Pecahan Setara', 'Latihan Pecahan'],
        )->assertRedirect()->assertSessionHasNoErrors();

        $lessons = Lesson::orderBy('id')->get();

        $this->assertCount(3, $lessons);
        $this->assertSame(['Pecahan Asas', 'Pecahan Setara', 'Latihan Pecahan'], $lessons->pluck('title')->all());

        foreach ($lessons as $lesson) {
            $this->assertSame($this->chapter->id, $lesson->chapter_id);
            $this->assertSame($this->teacher->id, $lesson->teacher_id);
            $this->assertSame(Lesson::SOURCE_UPLOAD, $lesson->source);
            Storage::disk('uploads')->assertExists($lesson->video_path);
        }
    }

    /** Every file gets its own stored video, not a shared one. */
    public function test_the_files_do_not_collide(): void
    {
        $this->submit([$this->video('a.mp4'), $this->video('b.mp4')])->assertRedirect();

        $paths = Lesson::pluck('video_path');

        $this->assertCount(2, $paths->unique(), 'two videos ended up pointing at one file');
    }

    /** A blank title must not shift the ones after it onto the wrong file. */
    public function test_a_blank_title_falls_back_without_shifting_the_rest(): void
    {
        $this->submit(
            [$this->video('satu.mp4'), $this->video('dua.mp4'), $this->video('tiga.mp4')],
            ['Pertama', '  ', 'Ketiga'],
        )->assertRedirect();

        $this->assertSame(['Pertama', 'dua', 'Ketiga'], Lesson::orderBy('id')->pluck('title')->all());
    }

    public function test_titles_are_optional(): void
    {
        $this->submit([$this->video('rakaman kelas.mp4')])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('rakaman kelas', Lesson::first()->title);
    }

    /**
     * Thumbnails are keyed by row, so a video whose frame could not be captured leaves a gap
     * rather than handing its neighbour's picture to the wrong video.
     */
    public function test_a_thumbnail_lands_on_the_video_it_was_captured_from(): void
    {
        $this->submit(
            [$this->video('a.mp4'), $this->video('b.mp4'), $this->video('c.mp4')],
            ['A', 'B', 'C'],
            ['thumbnails' => [
                // create() rather than image(): the image rule checks the type, not the pixels,
                // and image() needs the GD extension.
                0 => UploadedFile::fake()->create('a.jpg', 8, 'image/jpeg'),
                // 1 is absent: the browser could not decode that one.
                2 => UploadedFile::fake()->create('c.jpg', 8, 'image/jpeg'),
            ]],
        )->assertRedirect()->assertSessionHasNoErrors();

        $lessons = Lesson::orderBy('id')->get()->keyBy('title');

        $this->assertNotNull($lessons['A']->thumbnail_path);
        $this->assertNull($lessons['B']->thumbnail_path, 'B had no capture and must not borrow one');
        $this->assertNotNull($lessons['C']->thumbnail_path);
        $this->assertNotSame($lessons['A']->thumbnail_path, $lessons['C']->thumbnail_path);
    }

    public function test_at_least_one_video_is_required(): void
    {
        $this->actingAs($this->teacher)->post(route('cikgu.video.store'), [
            'chapter_id' => $this->chapter->id,
            'source' => Lesson::SOURCE_UPLOAD,
        ])->assertSessionHasErrors('videos');

        $this->assertSame(0, Lesson::count());
    }

    public function test_a_non_video_file_is_rejected(): void
    {
        $this->submit([$this->video('ok.mp4'), UploadedFile::fake()->create('nota.pdf', 20, 'application/pdf')])
            ->assertSessionHasErrors('videos.1');

        $this->assertSame(0, Lesson::count(), 'nothing should be saved when one file is rejected');
    }

    public function test_too_many_videos_is_rejected(): void
    {
        $files = array_map(fn (int $i) => $this->video("v{$i}.mp4", 10), range(1, LessonRequest::MAX_VIDEOS + 1));

        $this->submit($files)->assertSessionHasErrors('videos');

        $this->assertSame(0, Lesson::count());
    }

    /** A batch names its videos in the rows, so the shared title field is not required. */
    public function test_the_shared_title_is_not_required_for_a_batch(): void
    {
        $this->submit([$this->video('kelas.mp4')])->assertSessionHasNoErrors();
    }

    /** The YouTube path still makes exactly one video and still needs a title. */
    public function test_a_youtube_link_still_needs_a_title(): void
    {
        $this->actingAs($this->teacher)->post(route('cikgu.video.store'), [
            'chapter_id' => $this->chapter->id,
            'source' => Lesson::SOURCE_YOUTUBE,
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ])->assertSessionHasErrors('title');
    }

    public function test_a_youtube_link_saves_one_video(): void
    {
        $this->actingAs($this->teacher)->post(route('cikgu.video.store'), [
            'chapter_id' => $this->chapter->id,
            'source' => Lesson::SOURCE_YOUTUBE,
            'title' => 'Pautan Kelas',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(1, Lesson::count());
        $this->assertSame('Pautan Kelas', Lesson::first()->title);
    }

    /** Editing one video stays single-file and keeps its own title field. */
    public function test_editing_a_video_stays_single(): void
    {
        $lesson = Lesson::factory()->for($this->chapter)->create([
            'teacher_id' => $this->teacher->id,
            'source' => Lesson::SOURCE_UPLOAD,
            'title' => 'Lama',
        ]);

        $this->actingAs($this->teacher)->put(route('cikgu.video.update', $lesson), [
            'chapter_id' => $this->chapter->id,
            'source' => Lesson::SOURCE_UPLOAD,
            'title' => 'Baharu',
            'video' => $this->video('gantian.mp4'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Baharu', $lesson->fresh()->title);
        $this->assertSame(1, Lesson::count());
    }

    public function test_the_form_submits_files_and_their_titles(): void
    {
        $html = $this->actingAs($this->teacher)->get(route('cikgu.video.create'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]*name="videos\[\]"[^>]*multiple/', $html);
        $this->assertStringContainsString('name="video_titles[]"', $html);
        $this->assertStringContainsString('x-ref="thumbs"', $html);
    }
}
