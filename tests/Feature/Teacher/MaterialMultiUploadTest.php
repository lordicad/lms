<?php

namespace Tests\Feature\Teacher;

use App\Http\Requests\MaterialRequest;
use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The material page takes any number of files at once, each with its own title. The title is paired
 * to its file by position, so the cases worth holding are the ones where that pairing could slip.
 *
 * Editing stays single-file: it replaces what one material points at, which has no plural meaning.
 */
class MaterialMultiUploadTest extends TestCase
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

    private function pdf(string $name, int $kb = 40): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kb, 'application/pdf');
    }

    public function test_several_files_upload_at_once_each_with_its_own_title(): void
    {
        $this->actingAs($this->teacher)->post(route('cikgu.bahan.store'), [
            'chapter_id' => $this->chapter->id,
            'files' => [$this->pdf('nota.pdf'), $this->pdf('latihan.pdf'), $this->pdf('jawapan.pdf')],
            'titles' => ['Nota Pecahan', 'Lembaran Latihan', 'Skema Jawapan'],
        ])->assertRedirect(route('cikgu.bahan.index'))->assertSessionHasNoErrors();

        $materials = Material::orderBy('id')->get();

        $this->assertCount(3, $materials);
        $this->assertSame(['Nota Pecahan', 'Lembaran Latihan', 'Skema Jawapan'], $materials->pluck('title')->all());
        $this->assertSame(['nota.pdf', 'latihan.pdf', 'jawapan.pdf'], $materials->pluck('original_name')->all());

        foreach ($materials as $material) {
            $this->assertSame($this->chapter->id, $material->chapter_id);
            $this->assertSame($this->teacher->id, $material->teacher_id);
            Storage::disk('uploads')->assertExists($material->file_path);
        }
    }

    /** A blank title must not shift the ones after it onto the wrong files. */
    public function test_a_blank_title_falls_back_without_shifting_the_rest(): void
    {
        $this->actingAs($this->teacher)->post(route('cikgu.bahan.store'), [
            'chapter_id' => $this->chapter->id,
            'files' => [$this->pdf('satu.pdf'), $this->pdf('dua.pdf'), $this->pdf('tiga.pdf')],
            'titles' => ['Pertama', '  ', 'Ketiga'],
        ])->assertRedirect();

        $this->assertSame(['Pertama', 'dua', 'Ketiga'], Material::orderBy('id')->pluck('title')->all());
    }

    /** The "attach to a video" choice applies to the whole batch. */
    public function test_the_chosen_video_is_applied_to_every_file(): void
    {
        $lesson = Lesson::factory()->for($this->chapter)->create(['teacher_id' => $this->teacher->id]);

        $this->actingAs($this->teacher)->post(route('cikgu.bahan.store'), [
            'chapter_id' => $this->chapter->id,
            'lesson_id' => $lesson->id,
            'files' => [$this->pdf('a.pdf'), $this->pdf('b.pdf')],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame([$lesson->id, $lesson->id], Material::orderBy('id')->pluck('lesson_id')->all());
    }

    /** Left empty, they stay chapter-level — the behaviour the dropdown has always had. */
    public function test_files_can_be_left_unattached(): void
    {
        $this->actingAs($this->teacher)->post(route('cikgu.bahan.store'), [
            'chapter_id' => $this->chapter->id,
            'files' => [$this->pdf('sukatan.pdf')],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertNull(Material::first()->lesson_id);
    }

    public function test_at_least_one_file_is_required(): void
    {
        $this->actingAs($this->teacher)->post(route('cikgu.bahan.store'), [
            'chapter_id' => $this->chapter->id,
        ])->assertSessionHasErrors('files');

        $this->assertSame(0, Material::count());
    }

    public function test_a_disallowed_type_rejects_the_whole_batch(): void
    {
        $this->actingAs($this->teacher)->post(route('cikgu.bahan.store'), [
            'chapter_id' => $this->chapter->id,
            'files' => [$this->pdf('ok.pdf'), UploadedFile::fake()->create('macro.exe', 10)],
        ])->assertSessionHasErrors('files.1');

        $this->assertSame(0, Material::count(), 'nothing should be saved when one file is rejected');
    }

    public function test_too_many_files_is_rejected(): void
    {
        $files = array_map(fn (int $i) => $this->pdf("n{$i}.pdf", 5), range(1, MaterialRequest::MAX_FILES + 1));

        $this->actingAs($this->teacher)->post(route('cikgu.bahan.store'), [
            'chapter_id' => $this->chapter->id,
            'files' => $files,
        ])->assertSessionHasErrors('files');

        $this->assertSame(0, Material::count());
    }

    /** Editing one material still replaces its single file and keeps its own title field. */
    public function test_editing_a_material_stays_single_file(): void
    {
        $material = Material::factory()->create([
            'chapter_id' => $this->chapter->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Lama',
        ]);

        $this->actingAs($this->teacher)->put(route('cikgu.bahan.update', $material), [
            'chapter_id' => $this->chapter->id,
            'title' => 'Baharu',
            'file' => $this->pdf('gantian.pdf'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $material->refresh();
        $this->assertSame('Baharu', $material->title);
        $this->assertSame('gantian.pdf', $material->original_name);
        $this->assertSame(1, Material::count());
    }

    public function test_the_form_offers_the_drop_zone_and_a_title_per_file(): void
    {
        $html = $this->actingAs($this->teacher)->get(route('cikgu.bahan.create'))->assertOk()->getContent();

        $this->assertStringContainsString('tp-dropzone', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*name="files\[\]"[^>]*multiple/', $html);
        $this->assertStringContainsString('name="titles[]"', $html);
    }
}
