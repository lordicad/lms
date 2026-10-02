<?php

namespace Tests\Feature\Student;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A student may only download what a chapter page would show them. Material ids are sequential,
 * so before this any signed-in student could walk /muat-turun/bahan/1..N and pull files from
 * retired chapters and from lessons still in draft.
 */
class MaterialAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Chapter $chapter;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('uploads');

        $grade = Grade::factory()->level(4)->create();
        $this->chapter = Chapter::factory()->create(['grade_id' => $grade->id, 'is_active' => true]);
        $this->student = User::factory()->student(4)->create(['grade_id' => $grade->id]);
    }

    private function material(array $attributes = []): Material
    {
        $material = Material::factory()->create(['chapter_id' => $this->chapter->id] + $attributes);
        Storage::disk('uploads')->put($material->file_path, '%PDF-1.4');

        return $material;
    }

    public function test_a_student_can_download_an_ordinary_material(): void
    {
        $this->actingAs($this->student)->get(route('muat-turun.bahan', $this->material()))->assertOk();
    }

    public function test_a_student_cannot_download_a_material_on_a_draft_lesson(): void
    {
        $draft = Lesson::factory()->for($this->chapter)->draft()->create();
        $material = $this->material(['lesson_id' => $draft->id]);

        $this->actingAs($this->student)->get(route('muat-turun.bahan', $material))->assertForbidden();
    }

    public function test_a_student_cannot_download_from_a_retired_chapter(): void
    {
        $retired = Chapter::factory()->create(['grade_id' => $this->chapter->grade_id, 'is_active' => false]);
        $material = Material::factory()->create(['chapter_id' => $retired->id]);
        Storage::disk('uploads')->put($material->file_path, '%PDF-1.4');

        $this->actingAs($this->student)->get(route('muat-turun.bahan', $material))->assertForbidden();
    }

    public function test_the_material_appears_once_its_lesson_is_published(): void
    {
        $lesson = Lesson::factory()->for($this->chapter)->draft()->create();
        $material = $this->material(['lesson_id' => $lesson->id]);

        $lesson->update(['is_published' => true]);

        $this->actingAs($this->student)->get(route('muat-turun.bahan', $material))->assertOk();
    }

    public function test_the_chapter_page_hides_draft_lesson_materials_from_students(): void
    {
        $draft = Lesson::factory()->for($this->chapter)->draft()->create();
        $this->material(['lesson_id' => $draft->id, 'title' => 'Nota Rahsia Draf']);
        $this->material(['title' => 'Nota Biasa']);

        $this->actingAs($this->student)->get(route('bab.show', $this->chapter))
            ->assertOk()
            ->assertSee('Nota Biasa')
            ->assertDontSee('Nota Rahsia Draf');
    }

    public function test_a_teacher_can_still_open_a_draft_lesson_material(): void
    {
        $draft = Lesson::factory()->for($this->chapter)->draft()->create();
        $material = $this->material(['lesson_id' => $draft->id]);

        $this->actingAs(User::factory()->teacher()->create())
            ->get(route('muat-turun.bahan', $material))
            ->assertOk();
    }
}
