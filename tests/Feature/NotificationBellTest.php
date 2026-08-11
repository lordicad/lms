<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\TeacherNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * All three portals share one bell component, and each now has its own notification feed (teachers
 * when a student acts on their content, admins and students through their own feeds). The empty-state
 * tests below simply exercise the case where a given account has none yet: the point is that the
 * panel is real and honest rather than a button that does nothing, which is what it used to be.
 */
class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_teacher_bell_lists_notifications_and_counts_the_unread(): void
    {
        $teacher = User::factory()->teacher()->create();

        TeacherNotification::create([
            'teacher_id' => $teacher->id,
            'type' => TeacherNotification::TYPE_QUIZ,
            'actor_name' => 'Nurul',
            'title' => 'Kuiz Sains',
        ]);

        $html = $this->actingAs($teacher)->get(route('cikgu.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Nurul', $html);
        $this->assertStringContainsString('unread: 1', $html, 'the badge count is not wired to the query');
        $this->assertStringContainsString(route('cikgu.notifikasi'), $html);
    }

    public function test_the_admin_bell_opens_on_an_empty_panel(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('notifBell(', $html, 'the admin bell is not the shared component');
        $this->assertStringContainsString(__('Tiada notifikasi lagi'), $html);
        $this->assertStringContainsString('unread: 0', $html);
    }

    public function test_the_student_bell_opens_on_an_empty_panel(): void
    {
        $student = User::factory()->student(Grade::factory()->level(4)->create()->level)->create();

        $html = $this->actingAs($student)->get(route('profile.edit'))->assertOk()->getContent();

        $this->assertStringContainsString('notifBell(', $html, 'the student bell is not the shared component');
        $this->assertStringContainsString(__('Tiada notifikasi lagi'), $html);
        $this->assertStringContainsString('unread: 0', $html);
    }

    /**
     * The admin bell used to carry a hard-coded .tp-dot, so it always claimed unread items. With no
     * admin notifications in existence it could never be anything but wrong.
     */
    public function test_the_admin_bell_no_longer_claims_permanent_unread_items(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))->getContent();

        $this->assertStringNotContainsString('class="tp-dot"', $html);
    }

    /**
     * Each portal hands the bell its own mark-read endpoint, so opening the panel reports that
     * portal's notifications as read. The URL is passed to Alpine through @js(), which JSON-encodes
     * it — so the slashes come out escaped and a plain route() string would not match.
     */
    public function test_each_portal_wires_its_own_mark_read_endpoint(): void
    {
        $cases = [
            [User::factory()->teacher()->create(), route('cikgu.dashboard'), route('cikgu.notifikasi.baca')],
            [User::factory()->admin()->create(), route('admin.dashboard'), route('admin.notifikasi.baca')],
            [User::factory()->student(Grade::factory()->level(4)->create()->level)->create(), route('profile.edit'), route('belajar.notifikasi.baca')],
        ];

        foreach ($cases as [$user, $page, $markReadUrl]) {
            $html = $this->actingAs($user)->get($page)->assertOk()->getContent();
            $this->assertStringContainsString(
                \Illuminate\Support\Js::from($markReadUrl)->toHtml(),
                $html,
                "the bell on {$page} is not wired to {$markReadUrl}",
            );
        }
    }
}
