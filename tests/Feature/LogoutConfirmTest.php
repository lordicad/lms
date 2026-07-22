<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutConfirmTest extends TestCase
{
    use RefreshDatabase;

    private function assertLogoutIsGuarded(string $html, string $where): void
    {
        $this->assertStringContainsString('logout', $html, "no logout control on the {$where}");
        $this->assertStringContainsString(
            'Log keluar daripada akaun anda?',
            $html,
            "the logout on the {$where} has no confirmation",
        );
    }

    public function test_every_portal_asks_before_logging_out(): void
    {
        $grade = Grade::factory()->level(3)->create();

        $student = User::factory()->student($grade->level)->create();
        $this->assertLogoutIsGuarded(
            $this->actingAs($student)->get(route('profile.edit'))->getContent(), 'student profile',
        );

        $teacher = User::factory()->teacher()->create();
        $this->assertLogoutIsGuarded(
            $this->actingAs($teacher)->get(route('cikgu.dashboard'))->getContent(), 'teacher portal',
        );

        $admin = User::factory()->admin()->create();
        $this->assertLogoutIsGuarded(
            $this->actingAs($admin)->get(route('admin.dashboard'))->getContent(), 'admin portal',
        );
    }

    /** The only way out of the held first-password screen, so it is guarded too. */
    public function test_the_first_password_screen_asks_as_well(): void
    {
        $teacher = User::factory()->adminIssued()->teacher()->create();

        $this->assertLogoutIsGuarded(
            $this->actingAs($teacher)->get(route('password.first'))->getContent(), 'first-password screen',
        );
    }

    /** Confirming is a browser-side prompt; the POST itself must still sign the user out. */
    public function test_confirming_still_signs_the_user_out(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->post(route('logout'))->assertRedirect();
        $this->assertGuest();
    }
}
