<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Confirmation prompts, checked for being wired up rather than merely present.
 *
 * @js() renders a single-quoted JavaScript string, so the attribute around it has to be
 * double-quoted. Written the other way — onsubmit='return confirm('...')' — the attribute closes at
 * the first quote inside confirm(, the browser drops the malformed handler without complaint, and
 * the action goes through unconfirmed. It looks right in the source, and a "does the text appear?"
 * assertion passes happily, which is exactly how it went unnoticed.
 */
class LogoutConfirmTest extends TestCase
{
    use RefreshDatabase;

    /** The broken shape: a single-quoted attribute wrapped around a single-quoted string. */
    private const BROKEN = "onsubmit='return confirm('";

    private function assertConfirmIsWiredUp(string $html, string $needle, string $where): void
    {
        $this->assertStringNotContainsString(self::BROKEN, $html, "malformed confirm handler on the {$where}");

        $this->assertMatchesRegularExpression(
            '/onsubmit="return confirm\(\'[^"]*'.preg_quote($needle, '/').'/',
            $html,
            "the {$where} has no working confirmation for: {$needle}",
        );
    }

    public function test_every_portal_confirms_before_logging_out(): void
    {
        $grade = Grade::factory()->level(3)->create();
        $prompt = 'Log keluar daripada akaun anda?';

        $student = User::factory()->student($grade->level)->create();
        $this->assertConfirmIsWiredUp(
            $this->actingAs($student)->get(route('profile.edit'))->getContent(), $prompt, 'student profile',
        );

        $teacher = User::factory()->teacher()->create();
        $this->assertConfirmIsWiredUp(
            $this->actingAs($teacher)->get(route('cikgu.dashboard'))->getContent(), $prompt, 'teacher portal',
        );

        $admin = User::factory()->admin()->create();
        $this->assertConfirmIsWiredUp(
            $this->actingAs($admin)->get(route('admin.dashboard'))->getContent(), $prompt, 'admin portal',
        );
    }

    /** The only way out of the held first-password screen, so it is guarded too. */
    public function test_the_first_password_screen_confirms_as_well(): void
    {
        $teacher = User::factory()->adminIssued()->teacher()->create();

        $this->assertConfirmIsWiredUp(
            $this->actingAs($teacher)->get(route('password.first'))->getContent(),
            'Log keluar daripada akaun anda?',
            'first-password screen',
        );
    }

    /** The destructive actions were broken the same way, and they matter more than the logout. */
    public function test_deleting_a_video_confirms(): void
    {
        $teacher = User::factory()->teacher()->create();
        Lesson::factory()->for(Chapter::factory()->create())->create([
            'teacher_id' => $teacher->id, 'title' => 'Ujian',
        ]);

        $this->assertConfirmIsWiredUp(
            $this->actingAs($teacher)->get(route('cikgu.video.index'))->getContent(),
            'Padam video', 'video list',
        );
    }

    public function test_deleting_an_account_confirms(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->teacher()->create(['school_id' => $admin->school_id]);

        $this->assertConfirmIsWiredUp(
            $this->actingAs($admin)->get(route('admin.pengguna'))->getContent(),
            'Padam akaun', 'admin user list',
        );
    }

    /** No view may reintroduce the broken form, wherever it happens to render. */
    public function test_no_view_uses_a_single_quoted_confirm_attribute(): void
    {
        $offenders = [];
        $views = resource_path('views');

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($views));

        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            if (str_contains(file_get_contents($file->getPathname()), "onsubmit='")) {
                $offenders[] = str_replace($views.DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        $this->assertSame([], $offenders, 'these views would render a malformed confirm handler');
    }

    /** Confirming is browser-side only; the POST itself must still sign the user out. */
    public function test_confirming_still_signs_the_user_out(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->post(route('logout'))->assertRedirect();
        $this->assertGuest();
    }
}
