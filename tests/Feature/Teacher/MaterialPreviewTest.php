<?php

namespace Tests\Feature\Teacher;

use App\Models\Chapter;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * On the teacher's own material list, clicking a material's name opens a preview in place — the
 * same modal the admin content list uses — rather than only offering a download.
 */
class MaterialPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function material(string $name, string $mime): Material
    {
        $teacher = User::factory()->teacher()->create();

        return Material::factory()->create([
            'chapter_id' => Chapter::factory()->create()->id,
            'teacher_id' => $teacher->id,
            'title' => 'Nota Pecahan',
            'original_name' => $name,
            'mime_type' => $mime,
        ]);
    }

    private function indexAsOwner(Material $material): string
    {
        return $this->actingAs($material->teacher)->get(route('cikgu.bahan.index'))->assertOk()->getContent();
    }

    public function test_the_name_opens_a_preview(): void
    {
        $html = $this->indexAsOwner($this->material('nota.pdf', 'application/pdf'));

        $this->assertStringContainsString('open(', $html, 'the preview trigger is missing');
        $this->assertStringContainsString("item.kind === 'pdf'", $html, 'the preview modal is missing');
    }

    public function test_an_image_material_previews_as_an_image(): void
    {
        $html = $this->indexAsOwner($this->material('carta.png', 'image/png'));

        $this->assertStringContainsString("item.kind === 'image'", $html);
    }

    /** A file the browser cannot render still offers a download from the modal. */
    public function test_an_office_file_offers_a_download_in_the_modal(): void
    {
        $html = $this->indexAsOwner($this->material('slaid.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'));

        $this->assertStringContainsString("item.kind === 'none'", $html);
        $this->assertStringContainsString('downloadUrl', $html);
    }
}
