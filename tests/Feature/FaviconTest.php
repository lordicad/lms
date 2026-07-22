<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaviconTest extends TestCase
{
    use RefreshDatabase;

    private function assertHasFavicon(string $html, string $where): void
    {
        $this->assertStringContainsString('rel="icon"', $html, "no favicon on the {$where}");
        $this->assertStringContainsString('images/welearn1.png', $html, "wrong favicon on the {$where}");
    }

    public function test_every_shell_carries_the_welearn_tab_icon(): void
    {
        $grade = Grade::factory()->level(3)->create();

        // Signed out: landing and the auth shell.
        $this->assertHasFavicon($this->get('/')->getContent(), 'landing page');
        $this->assertHasFavicon($this->get(route('login'))->getContent(), 'sign-in page');

        // Each signed-in portal has its own shell.
        $student = User::factory()->student($grade->level)->create();
        $this->assertHasFavicon($this->actingAs($student)->get(route('belajar.index'))->getContent(), 'student portal');

        $teacher = User::factory()->teacher()->create();
        $this->assertHasFavicon($this->actingAs($teacher)->get(route('cikgu.dashboard'))->getContent(), 'teacher portal');

        $admin = User::factory()->admin()->create();
        $this->assertHasFavicon($this->actingAs($admin)->get(route('admin.dashboard'))->getContent(), 'admin portal');
    }
}
