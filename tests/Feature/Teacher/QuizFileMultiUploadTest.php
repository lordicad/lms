<?php

namespace Tests\Feature\Teacher;

use App\Http\Requests\QuizRequest;
use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uploading printed quizzes makes one quiz per file, each titled by its own row.
 *
 * Titles pair to files by position, so the cases worth holding are the ones where that could
 * slip: a blank title mid-list, a title that outruns its files. The interactive path is unchanged
 * and still makes exactly one quiz that needs a title.
 */
class QuizFileMultiUploadTest extends TestCase
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

    private function pdf(string $name, int $kb = 200): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kb, 'application/pdf');
    }

    /** @param array<int, UploadedFile> $files */
    private function submit(array $files, array $titles = [], array $extra = [])
    {
        return $this->actingAs($this->teacher)->post(route('cikgu.kuiz.store'), array_merge([
            'chapter_id' => $this->chapter->id,
            'type' => Quiz::TYPE_FILE,
            'files' => $files,
            'titles' => $titles,
            'is_published' => true,
        ], $extra));
    }

    public function test_each_file_becomes_its_own_printed_quiz(): void
    {
        $this->submit(
            [$this->pdf('kuiz1.pdf'), $this->pdf('kuiz2.pdf'), $this->pdf('kuiz3.pdf')],
            ['Kuiz Bab 1', 'Kuiz Bab 2', 'Kuiz Bab 3'],
        )->assertRedirect(route('cikgu.kuiz.index'))->assertSessionHasNoErrors();

        $quizzes = Quiz::orderBy('id')->get();

        $this->assertCount(3, $quizzes);
        $this->assertSame(['Kuiz Bab 1', 'Kuiz Bab 2', 'Kuiz Bab 3'], $quizzes->pluck('title')->all());

        foreach ($quizzes as $quiz) {
            $this->assertSame($this->chapter->id, $quiz->chapter_id);
            $this->assertSame($this->teacher->id, $quiz->teacher_id);
            $this->assertSame(Quiz::TYPE_FILE, $quiz->type);
            Storage::disk('uploads')->assertExists($quiz->file_path);
        }
    }

    /** Every file gets its own stored document, not a shared one. */
    public function test_the_files_do_not_collide(): void
    {
        $this->submit([$this->pdf('a.pdf'), $this->pdf('b.pdf')])->assertRedirect();

        $this->assertCount(2, Quiz::pluck('file_path')->unique(), 'two quizzes ended up pointing at one file');
    }

    /** A blank title must not shift the ones after it onto the wrong file. */
    public function test_a_blank_title_falls_back_without_shifting_the_rest(): void
    {
        $this->submit(
            [$this->pdf('satu.pdf'), $this->pdf('dua.pdf'), $this->pdf('tiga.pdf')],
            ['Pertama', '  ', 'Ketiga'],
        )->assertRedirect();

        $this->assertSame(['Pertama', 'dua', 'Ketiga'], Quiz::orderBy('id')->pluck('title')->all());
    }

    public function test_titles_are_optional(): void
    {
        $this->submit([$this->pdf('rancangan kuiz.pdf')])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('rancangan kuiz', Quiz::first()->title);
    }

    public function test_at_least_one_file_is_required(): void
    {
        $this->actingAs($this->teacher)->post(route('cikgu.kuiz.store'), [
            'chapter_id' => $this->chapter->id,
            'type' => Quiz::TYPE_FILE,
        ])->assertSessionHasErrors('files');

        $this->assertSame(0, Quiz::count());
    }

    public function test_a_wrong_type_of_file_is_rejected(): void
    {
        $this->submit([$this->pdf('ok.pdf'), UploadedFile::fake()->create('nota.png', 20, 'image/png')])
            ->assertSessionHasErrors('files.1');

        $this->assertSame(0, Quiz::count(), 'nothing should be saved when one file is rejected');
    }

    public function test_too_many_files_is_rejected(): void
    {
        $files = array_map(fn (int $i) => $this->pdf("k{$i}.pdf", 10), range(1, QuizRequest::MAX_FILES + 1));

        $this->submit($files)->assertSessionHasErrors('files');

        $this->assertSame(0, Quiz::count());
    }

    /** The interactive path still makes one quiz, still needs a title, and heads to the questions step. */
    public function test_an_interactive_quiz_still_needs_a_title(): void
    {
        $this->actingAs($this->teacher)->post(route('cikgu.kuiz.store'), [
            'chapter_id' => $this->chapter->id,
            'type' => Quiz::TYPE_INTERACTIVE,
        ])->assertSessionHasErrors('title');

        $this->assertSame(0, Quiz::count());
    }

    public function test_an_interactive_quiz_is_created_singly(): void
    {
        $this->actingAs($this->teacher)->post(route('cikgu.kuiz.store'), [
            'chapter_id' => $this->chapter->id,
            'type' => Quiz::TYPE_INTERACTIVE,
            'title' => 'Kuiz Interaktif',
            'is_published' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(1, Quiz::count());
        $quiz = Quiz::first();
        $this->assertSame('Kuiz Interaktif', $quiz->title);
        $this->assertSame(Quiz::TYPE_INTERACTIVE, $quiz->type);
        $this->assertNull($quiz->file_path);
    }
}
