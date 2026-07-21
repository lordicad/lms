<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserListColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_filter_shows_position_column_instead_of_year(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->teacher()->create(['name' => 'Cikgu Rohana', 'position' => 'Guru Kanan']);

        $response = $this->actingAs($admin)->get(route('admin.pengguna', ['role' => 'teacher']));

        $response->assertOk();
        $response->assertSee('Jawatan');       // Position header
        $response->assertSee('Guru Kanan');     // the teacher's position value
        $response->assertDontSee('>Tahun<', false);
    }

    public function test_student_filter_keeps_the_year_column(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->student()->create();

        $response = $this->actingAs($admin)->get(route('admin.pengguna', ['role' => 'student']));

        $response->assertOk();
        $response->assertSee('Tahun');
        $response->assertDontSee('Jawatan');
    }
}
