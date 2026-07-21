<?php
namespace Tests\Feature;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentWelcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_greets_the_student_by_their_nickname(): void
    {
        $grade = Grade::factory()->level(3)->create();
        $student = User::factory()->student($grade->level)->create([
            'name' => 'Nurul Ain Binti Khairuddin',
            'username' => 'Ain',
            'email' => 'ain@moe.edu.my',
        ]);

        $html = $this->actingAs($student)->get(route('belajar.index'))->assertOk()->getContent();

        // The nickname, not the formal name on record.
        $this->assertStringContainsString('Selamat datang, Ain', $html);
        $this->assertStringContainsString('Ringkasan pembelajaran anda pada hari ini', $html);
    }

    /** It sits above the year check, so it shows even before a Tahun is set. */
    public function test_the_greeting_shows_even_without_a_year(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'grade_id' => null,
            'username' => 'Baru',
            'email' => 'baru@moe.edu.my',
        ]);

        $html = $this->actingAs($student)->get(route('belajar.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Selamat datang, Baru', $html);
    }
}
