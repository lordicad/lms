<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\TeacherNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * All three portals share one bell component.
 *
 * Only teachers have notifications — every TeacherNotification::record() call fires when a student
 * acts on a teacher's content — so the admin and student bells open on their empty state. That is
 * deliberate, and the assertions below say so: the point is that the panel is real and honest
 * rather than a button that does nothing, which is what both used to be.
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
     * markRead() posts to a route that only the teacher portal has. The other two must not be
     * handed a URL to call — the component skips the request when there is none.
     */
    public function test_only_the_teacher_bell_reports_notifications_as_read(): void
    {
        $teacherHtml = $this->actingAs(User::factory()->teacher()->create())
            ->get(route('cikgu.dashboard'))->getContent();

        // The URL is handed to Alpine through @js(), which JSON-encodes it — so the slashes come
        // out escaped and a plain route() string will not match.
        $this->assertStringContainsString(
            \Illuminate\Support\Js::from(route('cikgu.notifikasi.baca'))->toHtml(),
            $teacherHtml,
        );

        $adminHtml = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))->getContent();
        $this->assertStringContainsString('markReadUrl: null', $adminHtml);
    }
}
