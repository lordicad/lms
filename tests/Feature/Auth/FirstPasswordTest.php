<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FirstPasswordTest extends TestCase
{
    use RefreshDatabase;

    /** An account as the admin hands it over: a password they chose, never replaced by the owner. */
    private function adminIssued(string $role = User::ROLE_TEACHER): User
    {
        return User::factory()->adminIssued()->create([
            'role' => $role,
            'password' => Hash::make('issued-by-admin'),
        ]);
    }

    public function test_admin_issued_account_is_held_on_the_first_password_screen(): void
    {
        $teacher = $this->adminIssued();

        $this->actingAs($teacher)->get(route('cikgu.dashboard'))
            ->assertRedirect(route('password.first'));
    }

    public function test_students_are_held_too(): void
    {
        $student = $this->adminIssued(User::ROLE_STUDENT);

        $this->actingAs($student)->get(route('belajar.index'))
            ->assertRedirect(route('password.first'));
    }

    public function test_the_first_password_screen_itself_is_reachable_so_the_redirect_cannot_loop(): void
    {
        $teacher = $this->adminIssued();

        $this->actingAs($teacher)->get(route('password.first'))->assertOk();
    }

    public function test_logout_stays_reachable_while_held(): void
    {
        $teacher = $this->adminIssued();

        $this->actingAs($teacher)->post(route('logout'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_setting_a_password_releases_the_account_and_signs_in_with_it(): void
    {
        $teacher = $this->adminIssued();

        $this->actingAs($teacher)->post(route('password.first.store'), [
            'password' => 'my-own-secret',
            'password_confirmation' => 'my-own-secret',
        ])->assertRedirect($teacher->homeRoute());

        $teacher->refresh();
        $this->assertNotNull($teacher->password_changed_at);
        $this->assertTrue(Hash::check('my-own-secret', $teacher->password));

        // No longer held: the dashboard opens normally.
        $this->actingAs($teacher)->get(route('cikgu.dashboard'))->assertOk();
    }

    public function test_the_new_password_must_be_confirmed_and_long_enough(): void
    {
        $teacher = $this->adminIssued();

        $this->actingAs($teacher)->post(route('password.first.store'), [
            'password' => 'abc',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');

        $this->assertNull($teacher->fresh()->password_changed_at);
    }

    public function test_an_account_that_owns_its_password_is_not_held(): void
    {
        $teacher = User::factory()->teacher()->create();
        $teacher->markPasswordChanged();

        $this->actingAs($teacher)->get(route('cikgu.dashboard'))->assertOk();
    }

    public function test_admins_are_never_held(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->forceFill(['password_changed_at' => null])->save();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }
}
