<?php

namespace Tests\Feature;

use App\Models\TeacherNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The notification list renders its message with {!! !!} so an intended <strong> around the actor
 * survives. Known types drop the title into an escaped :title placeholder; the unknown-type fallback
 * used the raw title, so a title carrying HTML would render as live markup. The fallback must escape
 * too - proven here with a type the view has no $meta entry for.
 */
class NotificationEscapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unknown_type_notification_title_is_escaped(): void
    {
        $teacher = User::factory()->teacher()->create();

        TeacherNotification::create([
            'teacher_id' => $teacher->id,
            'type' => 'some_future_type_without_meta',   // no $meta entry -> hits the fallback branch
            'actor_name' => 'Adam',
            'title' => '<img src=x onerror="window.__xss=1">',
        ]);

        $html = $this->actingAs($teacher)->get(route('cikgu.notifikasi'))->assertOk()->getContent();

        // The payload must appear declawed, never as a real tag that the browser would execute.
        $this->assertStringContainsString('&lt;img src=x', $html);
        $this->assertStringNotContainsString('<img src=x onerror', $html);
    }
}
