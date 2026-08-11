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
 * Logout and the destructive actions (delete video, delete account) now confirm through the styled
 * modal components <x-logout-confirm> and <x-confirm-modal> instead of the browser's native
 * confirm(). Each renders a hidden POST/DELETE form and a modal whose confirm button submits it via
 * the HTML `form` attribute - so "wired up" here means: the real form points at the right route, and
 * the confirm modal is present to guard it.
 *
 * The first-password screen is the one place still on native confirm(), so the onsubmit-quoting
 * guard below (the helper and the no-single-quote scan) remains for it. @js() renders a single-quoted
 * string, so its attribute must be double-quoted; onsubmit='return confirm('...')' would close at the
 * first inner quote, the browser would drop the handler silently, and the action would go through
 * unconfirmed - which a "does the text appear?" assertion would miss.
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

    /**
     * A modal-guarded action: the hidden form targets $action, and a confirm modal (identified by
     * $confirmClass - lc-confirm for logout, cm-confirm for destructive) is present to submit it.
     */
    private function assertModalGuards(string $html, string $action, string $confirmClass, string $prompt, string $where): void
    {
        $this->assertStringContainsString('action="'.$action.'"', $html, "the {$where} has no form for the action");
        $this->assertStringContainsString($confirmClass, $html, "the {$where} action is not behind a confirm modal");
        $this->assertStringContainsString($prompt, $html, "the {$where} confirm modal has no prompt");
    }

    public function test_every_portal_confirms_before_logging_out(): void
    {
        $grade = Grade::factory()->level(3)->create();
        $logout = route('logout');
        $prompt = __('Anda akan dilog keluar daripada akaun anda.');

        $portals = [
            'student profile' => [User::factory()->student($grade->level)->create(), route('profile.edit')],
            'teacher portal' => [User::factory()->teacher()->create(), route('cikgu.dashboard')],
            'admin portal' => [User::factory()->admin()->create(), route('admin.dashboard')],
        ];

        foreach ($portals as $where => [$user, $url]) {
            $html = $this->actingAs($user)->get($url)->assertOk()->getContent();
            $this->assertModalGuards($html, $logout, 'lc-confirm', $prompt, $where);
        }
    }

    /** The only way out of the held first-password screen, so it is guarded too (still native confirm). */
    public function test_the_first_password_screen_confirms_as_well(): void
    {
        $teacher = User::factory()->adminIssued()->teacher()->create();

        $this->assertConfirmIsWiredUp(
            $this->actingAs($teacher)->get(route('password.first'))->getContent(),
            'Log keluar daripada akaun anda?',
            'first-password screen',
        );
    }

    /** The destructive actions confirm through <x-confirm-modal>, and they matter more than the logout. */
    public function test_deleting_a_video_confirms(): void
    {
        $teacher = User::factory()->teacher()->create();
        $lesson = Lesson::factory()->for(Chapter::factory()->create())->create([
            'teacher_id' => $teacher->id, 'title' => 'Ujian',
        ]);

        $this->assertModalGuards(
            $this->actingAs($teacher)->get(route('cikgu.video.index'))->getContent(),
            route('cikgu.video.destroy', $lesson), 'cm-confirm', __('Padam video?'), 'video list',
        );
    }

    public function test_deleting_an_account_confirms(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->teacher()->create(['school_id' => $admin->school_id]);

        $this->assertModalGuards(
            $this->actingAs($admin)->get(route('admin.pengguna'))->getContent(),
            route('admin.pengguna.destroy', $target), 'cm-confirm', __('Padam akaun?'), 'admin user list',
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
