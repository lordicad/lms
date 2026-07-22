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

    /**
     * Dragging is one way in; clicking the zone must still reach a picker. It opens the shared
     * picker rather than the video input directly, because the zone now takes attachments too and
     * take() is what sorts them into the right input.
     */
    public function test_the_zone_still_opens_the_file_picker(): void
    {
        $html = $this->form();

        $this->assertStringContainsString('@click="$refs.picker.click()"', $html);
        $this->assertStringContainsString('x-ref="picker"', $html);
        $this->assertStringContainsString('x-ref="video"', $html);
    }

    /**
     * Every value handed to videoForm() must actually be destructured by it.
     *
     * The component takes a fixed list of keys, so adding one to the payload without adding it to
     * the signature leaves it undefined at runtime — and the failure is silent. That is exactly how
     * dropping a PDF stopped working: allowedExtensions never arrived, so isAllowedAttachment()
     * called .includes() on undefined and the file went nowhere.
     *
     * PHPUnit cannot run the JavaScript, but it can check that the two lists agree.
     */
    public function test_every_value_passed_to_the_component_is_read_by_it(): void
    {
        $html = $this->form();

        // @js() renders as JSON.parse('...') with the quotes escaped as \u0022, so the inner
        // string is decoded once to get back the JSON text and again to get the payload.
        $this->assertSame(1, preg_match("/videoForm\\(JSON\\.parse\\('(.*?)'\\)\\)/s", $html, $call));

        $payload = json_decode(json_decode('"'.$call[1].'"'), true);
        $this->assertIsArray($payload, 'the x-data payload did not decode');

        $this->assertSame(1, preg_match('/function videoForm\(\{([^}]*)\}\)/', $html, $signature));
        $destructured = array_map('trim', explode(',', $signature[1]));

        foreach (array_keys($payload) as $key) {
            $this->assertContains(
                $key,
                $destructured,
                "videoForm() is given \"{$key}\" but never destructures it, so it is undefined at runtime",
            );
        }
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

        foreach (['Seret & lepaskan fail di sini', 'Tambah Fail', 'Nama paparan (untuk pelajar)'] as $label) {
            $this->assertStringContainsString(e($label), $html, "the drop zone is missing: {$label}");
        }

        // take() reports these by name when a file is the wrong type, too big, or one too many.
        foreach (['badType', 'attachmentTooBig', 'tooManyFiles'] as $key) {
            $this->assertStringContainsString($key, $html, "take() reads labels.{$key}, which is not defined");
        }
    }
}
