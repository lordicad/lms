<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Private uploads (videos, materials, printable quizzes) are reachable only through the signed,
 * expiring URLs the app hands out - not by a bare or tampered link, and not after expiry.
 */
class PrivateMediaTest extends TestCase
{
    use RefreshDatabase;

    private const BYTES = '0123456789abcdefghij';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('uploads');
        Storage::disk('uploads')->put('videos/clip.mp4', self::BYTES);
        Storage::disk('uploads')->put('materials/nota.pdf', '%PDF-1.4 test');
    }

    private function lesson(): Lesson
    {
        return Lesson::factory()->for(Chapter::factory())->create(['video_path' => 'videos/clip.mp4']);
    }

    public function test_the_signed_video_url_plays_without_a_login(): void
    {
        // No actingAs: the signature is the permission, which is what the Flutter app relies on.
        $this->get($this->lesson()->videoUrl())->assertOk();
    }

    public function test_seeking_still_works(): void
    {
        $response = $this->get($this->lesson()->videoUrl(), ['Range' => 'bytes=10-14']);

        $response->assertStatus(206);
        $this->assertSame('abcde', $response->streamedContent());
    }

    public function test_an_unsigned_or_tampered_link_is_refused(): void
    {
        $lesson = $this->lesson();

        $this->get(route('media.lesson', $lesson))->assertForbidden();
        $this->get($lesson->videoUrl().'x')->assertForbidden();
    }

    public function test_a_link_stops_working_once_it_expires(): void
    {
        $url = $this->lesson()->videoUrl();

        $this->travel(config('lms.media_url_ttl_minutes') + 1)->minutes();

        $this->get($url)->assertForbidden();
    }

    public function test_a_signature_cannot_be_moved_to_another_file(): void
    {
        $mine = $this->lesson();
        $other = Lesson::factory()->for(Chapter::factory())->create(['video_path' => 'videos/clip.mp4']);

        $this->get(str_replace("/video/{$mine->id}?", "/video/{$other->id}?", $mine->videoUrl()))->assertForbidden();
    }

    public function test_material_previews_use_a_signed_link(): void
    {
        $material = Material::factory()->create(['file_path' => 'materials/nota.pdf']);

        $this->assertStringNotContainsString('/uploads/', $material->fileUrl());
        $this->get($material->fileUrl())->assertOk();
    }

    public function test_the_migration_closes_the_private_folders_only(): void
    {
        $migration = require database_path('migrations/2026_10_02_000000_protect_private_uploads.php');
        $migration->up();

        foreach (['videos', 'materials', 'quizzes'] as $folder) {
            $this->assertStringContainsString('Require all denied', Storage::disk('uploads')->get("{$folder}/.htaccess"));
        }

        Storage::disk('uploads')->assertMissing('thumbnails/.htaccess');
        Storage::disk('uploads')->assertMissing('avatars/.htaccess');
    }
}
