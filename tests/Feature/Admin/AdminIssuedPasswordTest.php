<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminIssuedPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'role' => User::ROLE_TEACHER,
            'name' => 'Cikgu Baharu',
            'username' => 'cikgubaharu',
            'email' => 'baharu@moe.gov.my',
            'password' => 'handed-over',
            'password_confirmation' => 'handed-over',
            'is_active' => 1,
        ], $overrides);
    }

    public function test_an_account_the_admin_creates_starts_needing_its_own_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.pengguna.store'), $this->payload())
            ->assertRedirect(route('admin.pengguna'));

        $created = User::where('username', 'cikgubaharu')->firstOrFail();
        $this->assertNull($created->password_changed_at);
        $this->assertTrue($created->mustChangePassword());
    }

    public function test_an_admin_password_reset_asks_the_owner_to_choose_again(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $teacher->markPasswordChanged();
        $this->assertFalse($teacher->fresh()->mustChangePassword());

        $this->actingAs($admin)->put(route('admin.pengguna.update', $teacher), $this->payload([
            'name' => $teacher->name,
            'username' => $teacher->username,
            'email' => $teacher->email,
            'password' => 'new-issued',
            'password_confirmation' => 'new-issued',
        ]))->assertRedirect(route('admin.pengguna'));

        $this->assertTrue($teacher->fresh()->mustChangePassword());
    }

    public function test_editing_without_a_password_leaves_the_owners_password_alone(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $teacher->markPasswordChanged();

        $payload = $this->payload([
            'name' => 'Nama Dikemas Kini',
            'username' => $teacher->username,
            'email' => $teacher->email,
        ]);
        unset($payload['password'], $payload['password_confirmation']);

        $this->actingAs($admin)->put(route('admin.pengguna.update', $teacher), $payload)
            ->assertRedirect(route('admin.pengguna'));

        $teacher->refresh();
        $this->assertSame('Nama Dikemas Kini', $teacher->name);
        $this->assertFalse($teacher->mustChangePassword());
    }
}
