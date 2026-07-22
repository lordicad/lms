<?php

namespace Tests\Feature\Teacher;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The upload field is now a drop zone. The real <input type="file"> is still there and is still what
 * submits — it is only hidden behind .sr-only — so a regression that dropped it would leave a form
 * that looks fine and uploads nothing. These assertions keep both halves honest.
 */
class VideoDropzoneTest extends TestCase
{
    use RefreshDatabase;

    private function form(): string
    {
        return $this->actingAs(User::factory()->teacher()->create())
            ->get(route('cikgu.video.create'))
            ->assertOk()
            ->getContent();
    }

    public function test_the_upload_field_offers_a_drop_zone(): void
    {
        $html = $this->form();

        $this->assertStringContainsString('tp-dropzone', $html);
        $this->assertStringContainsString('@drop.prevent="onDrop($event)"', $html);
        $this->assertStringContainsString('dragging', $html);
    }

    /** Dragging is the new way in; clicking the zone must still reach the picker. */
    public function test_the_zone_still_opens_the_file_picker(): void
    {
        $html = $this->form();

        $this->assertStringContainsString('@click="$refs.video.click()"', $html);
        $this->assertStringContainsString('x-ref="video"', $html);
    }

    /** Hidden, but present, named and still accepting the same formats. */
    public function test_the_real_file_input_survives(): void
    {
        $html = $this->form();

        $this->assertMatchesRegularExpression(
            '/<input id="video" name="video" type="file"[^>]*class="sr-only"/',
            $html,
            'the file input that actually submits is missing or no longer named "video"',
        );
        $this->assertStringContainsString('accept=".mp4,.webm,video/mp4,video/webm"', $html);
    }

    /** Every label the zone shows comes from a translation the Alpine component can read. */
    public function test_the_zone_labels_are_translated(): void
    {
        $html = $this->form();

        foreach (['Seret fail video ke sini', 'Pilih Fail', 'Tukar Fail'] as $label) {
            $this->assertStringContainsString($label, $html, "the drop zone is missing: {$label}");
        }

        $this->assertStringContainsString('notVideo', $html, 'onDrop() reads labels.notVideo, which is not defined');
    }
}
