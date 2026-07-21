<?php
namespace Tests\Feature;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileCollapseTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        $grade = Grade::factory()->level(3)->create();

        return User::factory()->student($grade->level)->create(['email' => 'murid@moe.gov.my']);
    }

    public function test_both_panels_start_closed(): void
    {
        $html = $this->actingAs($this->student())->get(route('profile.edit'))->getContent();

        $this->assertStringContainsString('id="akaun-panel"', $html);
        $this->assertStringContainsString('id="kata-laluan-panel"', $html);
        // Neither section has errors, so both render closed.
        $this->assertSame(2, substr_count($html, 'x-data="{ open: false }"'));
        $this->assertStringNotContainsString('x-data="{ open: true }"', $html);
        // Balanced markup: every <section> is closed.
        $this->assertSame(substr_count($html, '<section'), substr_count($html, '</section>'));
    }

    public function test_the_account_panel_opens_when_that_form_has_errors(): void
    {
        $student = $this->student();

        $html = $this->actingAs($student)
            ->from(route('profile.edit'))
            ->followingRedirects()
            ->patch(route('profile.update'), ['name' => '', 'username' => ''])   // invalid
            ->getContent();

        $this->assertStringContainsString('x-data="{ open: true }"', $html);
    }

    public function test_the_password_panel_opens_when_the_password_form_has_errors(): void
    {
        $student = $this->student();

        $html = $this->actingAs($student)
            ->from(route('profile.edit'))
            ->followingRedirects()
            ->put(route('password.update'), [
                'current_password' => 'wrong-one',
                'password' => 'abc',
                'password_confirmation' => 'different',
            ])
            ->getContent();

        // Exactly one panel opens — the password one, not the account one.
        $this->assertSame(1, substr_count($html, 'x-data="{ open: true }"'));
        $this->assertStringContainsString('kata-laluan-panel', $html);
    }
}
