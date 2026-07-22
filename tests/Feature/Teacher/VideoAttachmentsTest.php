<?php

namespace Tests\Feature\Teacher;

use App\Http\Requests\LessonRequest;
use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Files dropped alongside a video are saved as Materials on that lesson, each with the display
 * name the teacher typed. The name is paired to the file by position, so the interesting cases are
 * the ones where that pairing could slip: a blank name in the middle, more files than names.
 */
class VideoAttachmentsTest extends TestCase
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

    /** @param array<int, UploadedFile> $attachments */
    private function payload(array $attachments = [], array $titles = []): array
    {
        return array_filter([
            'chapter_id' => $this->chapter->id,
            'title' => 'Pecahan Asas',
            'source' => Lesson::SOURCE_UPLOAD,
            'video' => UploadedFile::fake()->create('kelas.mp4', 512, 'video/mp4'),
            'attachments' => $attachments,
            'attachment_titles' => $titles,
        ], fn ($v) => $v !== []);
    }

    /** The form has to submit both halves of the pair, or the names never reach the server. */
    public function test_the_form_submits_files_and_their_display_names(): void
    {
        $html = $this->actingAs($this->teacher)->get(route('cikgu.video.create'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]*name="attachments\[\]"[^>]*multiple/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*name="attachment_titles\[\]"[^>]*maxlength="100"/', $html);

        // The picker is deliberately unnamed: it only sorts files into the two inputs above.
        $this->assertStringContainsString('x-ref="picker"', $html);
        $this->assertStringContainsString('x-ref="attachments"', $html);
    }

    public function test_files_dropped_with_the_video_become_materials_on_that_lesson(): void
    {
        $this->actingAs($this->teacher)->post(route('cikgu.video.store'), $this->payload([
            UploadedFile::fake()->create('nota.pdf', 200, 'application/pdf'),
            UploadedFile::fake()->create('latihan.docx', 120, 'application/msword'),
        ], ['Nota Pecahan', 'Lembaran Latihan']))->assertRedirect();

        $lesson = Lesson::firstWhere('title', 'Pecahan Asas');
        $materials = Material::where('lesson_id', $lesson->id)->orderBy('id')->get();

        $this->assertCount(2, $materials);
        $this->assertSame(['Nota Pecahan', 'Lembaran Latihan'], $materials->pluck('title')->all());
        $this->assertSame(['nota.pdf', 'latihan.docx'], $materials->pluck('original_name')->all());

        // They belong to the same chapter and teacher as the video, so the Bahan page lists them.
        $this->assertSame([$this->chapter->id, $this->chapter->id], $materials->pluck('chapter_id')->all());
        $this->assertSame([$this->teacher->id, $this->teacher->id], $materials->pluck('teacher_id')->all());

        foreach ($materials as $material) {
            Storage::disk('uploads')->assertExists($material->file_path);
        }
    }

    /** A blank name must not shift the ones after it onto the wrong files. */
    public function test_a_blank_display_name_falls_back_to_the_file_name_without_shifting_the_rest(): void
    {
        $this->actingAs($this->teacher)->post(route('cikgu.video.store'), $this->payload([
            UploadedFile::fake()->create('satu.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->create('dua.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->create('tiga.pdf', 10, 'application/pdf'),
        ], ['Pertama', '   ', 'Ketiga']))->assertRedirect();

        $materials = Material::orderBy('id')->get();

        $this->assertSame(['Pertama', 'dua', 'Ketiga'], $materials->pluck('title')->all());
        $this->assertSame(['satu.pdf', 'dua.pdf', 'tiga.pdf'], $materials->pluck('original_name')->all());
    }

    /** Fewer names than files is legal — the rest fall back rather than erroring. */
    public function test_files_without_a_name_use_their_own(): void
    {
        $this->actingAs($this->teacher)->post(route('cikgu.video.store'), $this->payload([
            UploadedFile::fake()->create('rancangan mengajar.pdf', 10, 'application/pdf'),
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('rancangan mengajar', Material::first()->title);
    }

    public function test_a_disallowed_file_type_is_rejected(): void
    {
        $this->actingAs($this->teacher)->post(route('cikgu.video.store'), $this->payload([
            UploadedFile::fake()->create('macro.exe', 10, 'application/x-msdownload'),
        ]))->assertSessionHasErrors('attachments.0');

        $this->assertSame(0, Material::count());
        $this->assertSame(0, Lesson::count(), 'the lesson must not be created when an attachment is rejected');
    }

    public function test_an_oversized_attachment_is_rejected(): void
    {
        $overBy = (config('lms.material_max_mb') * 1024) + 100;

        $this->actingAs($this->teacher)->post(route('cikgu.video.store'), $this->payload([
            UploadedFile::fake()->create('besar.pdf', $overBy, 'application/pdf'),
        ]))->assertSessionHasErrors('attachments.0');

        $this->assertSame(0, Material::count());
    }

    public function test_more_attachments_than_allowed_is_rejected(): void
    {
        $tooMany = array_map(
            fn (int $i) => UploadedFile::fake()->create("nota{$i}.pdf", 10, 'application/pdf'),
            range(1, LessonRequest::MAX_ATTACHMENTS + 1),
        );

        $this->actingAs($this->teacher)->post(route('cikgu.video.store'), $this->payload($tooMany))
            ->assertSessionHasErrors('attachments');

        $this->assertSame(0, Material::count());
    }

    /** Attachments are optional: the plain video upload still works untouched. */
    public function test_a_video_with_no_attachments_still_saves(): void
    {
        $this->actingAs($this->teacher)->post(route('cikgu.video.store'), $this->payload())
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(1, Lesson::count());
        $this->assertSame(0, Material::count());
    }

    /** Editing a video can add more, without disturbing what is already attached. */
    public function test_editing_a_video_adds_to_its_attachments(): void
    {
        $lesson = Lesson::factory()->for($this->chapter)->create(['teacher_id' => $this->teacher->id]);
        Material::factory()->create([
            'lesson_id' => $lesson->id,
            'chapter_id' => $this->chapter->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Sedia Ada',
        ]);

        $this->actingAs($this->teacher)->put(route('cikgu.video.update', $lesson), [
            'chapter_id' => $this->chapter->id,
            'title' => $lesson->title,
            'source' => Lesson::SOURCE_UPLOAD,
            'attachments' => [UploadedFile::fake()->create('tambahan.pdf', 10, 'application/pdf')],
            'attachment_titles' => ['Nota Tambahan'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(
            ['Sedia Ada', 'Nota Tambahan'],
            Material::where('lesson_id', $lesson->id)->orderBy('id')->pluck('title')->all(),
        );
    }
}
