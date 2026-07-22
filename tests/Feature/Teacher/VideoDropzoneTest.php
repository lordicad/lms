<?php

namespace Tests\Feature\Teacher;

use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The client half of the video form: the drop zone, and the inputs that actually submit.
 *
 * What each form does with the files is covered by VideoMultiUploadTest. This is about the markup
 * being wired up at all — a zone that looks right while submitting nothing would pass every
 * server-side test in the suite.
 */
class VideoDropzoneTest extends TestCase
{
    use RefreshDatabase;

    private function createForm(): string
    {
        return $this->actingAs(User::factory()->teacher()->create())
            ->get(route('cikgu.video.create'))
            ->assertOk()
            ->getContent();
    }

    private function editForm(): string
    {
        $teacher = User::factory()->teacher()->create();
        $lesson = Lesson::factory()->for(Chapter::factory()->create())->create([
            'teacher_id' => $teacher->id,
            'source' => Lesson::SOURCE_UPLOAD,
        ]);

        return $this->actingAs($teacher)->get(route('cikgu.video.edit', $lesson))->assertOk()->getContent();
    }

    public function test_the_create_form_offers_a_drop_zone(): void
    {
        $html = $this->createForm();

        $this->assertStringContainsString('tp-dropzone', $html);
        $this->assertStringContainsString('take($event.dataTransfer?.files)', $html);
        $this->assertStringContainsString('dragging', $html);
    }

    /** Dragging is one way in; clicking the zone must still reach a picker. */
    public function test_the_zone_opens_a_file_picker(): void
    {
        $this->assertStringContainsString('@click="$refs.picker.click()"', $this->createForm());
    }

    /** Hidden, but present and named: this is what carries the files to the server. */
    public function test_the_create_form_submits_a_multi_file_input(): void
    {
        $html = $this->createForm();

        $this->assertMatchesRegularExpression(
            '/<input type="file" name="videos\[\]" multiple[^>]*class="sr-only"/',
            $html,
            'the input that actually submits the batch is missing or renamed',
        );
        $this->assertStringContainsString('x-ref="videos"', $html);
        $this->assertStringContainsString('x-ref="thumbs"', $html);
    }

    /** Editing one video keeps the single input it has always had. */
    public function test_the_edit_form_keeps_a_single_file_input(): void
    {
        $html = $this->editForm();

        $this->assertMatchesRegularExpression('/<input id="video" name="video" type="file"[^>]*class="sr-only"/', $html);
        $this->assertStringNotContainsString('name="videos[]"', $html, 'editing must not offer a batch');
    }

    public function test_the_zone_labels_are_translated(): void
    {
        $html = $this->createForm();

        foreach (['Seret & lepaskan fail di sini', 'Tambah Fail', 'Tajuk video'] as $label) {
            $this->assertStringContainsString(e($label), $html, "the drop zone is missing: {$label}");
        }

        // take() reports these by name when a file is the wrong kind, too big, or one too many.
        foreach (['notVideo', 'tooBig', 'tooManyFiles'] as $key) {
            $this->assertStringContainsString($key, $html, "take() reads labels.{$key}, which is not defined");
        }
    }

    /**
     * Every value handed to videoForm() must actually be destructured by it.
     *
     * The component takes a fixed list of keys, so adding one to the payload without adding it to
     * the signature leaves it undefined at runtime — and the failure is silent. That is exactly how
     * dropping a PDF stopped working once: allowedExtensions never arrived, so the check that read
     * it threw inside the drop handler and the file went nowhere.
     *
     * PHPUnit cannot run the JavaScript, but it can check that the two lists agree.
     */
    public function test_every_value_passed_to_the_component_is_read_by_it(): void
    {
        $html = $this->createForm();

        // @js() renders as JSON.parse('...') with the quotes escaped as ", so the inner
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
}
