<?php

namespace Tests\Feature\Admin;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserListDefaultRoleTest extends TestCase
{
    use RefreshDatabase;

    private function seedBoth(): void
    {
        $grade = Grade::factory()->level(6)->create();
        User::factory()->teacher()->create(['name' => 'Cikgu Rohana']);
        User::factory()->student($grade->level)->create(['name' => 'Murid Aisyah']);
    }

    public function test_the_list_opens_on_teachers(): void
    {
        $this->seedBoth();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengguna'));

        $response->assertOk();
        $this->assertSame(User::ROLE_TEACHER, $response->viewData('role'));
        $response->assertSee('Cikgu Rohana');
        $response->assertDontSee('Murid Aisyah');
    }

    public function test_switching_to_students_shows_students(): void
    {
        $this->seedBoth();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengguna', ['role' => 'student']));

        $response->assertOk();
        $this->assertSame(User::ROLE_STUDENT, $response->viewData('role'));
        $response->assertSee('Murid Aisyah');
        $response->assertDontSee('Cikgu Rohana');
    }

    /** Choosing "All roles" sends an empty value, which must beat the Teacher default. */
    public function test_choosing_all_roles_shows_everyone(): void
    {
        $this->seedBoth();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pengguna', ['role' => '']));

        $response->assertOk();
        $this->assertNull($response->viewData('role'));
        $response->assertSee('Cikgu Rohana');
        $response->assertSee('Murid Aisyah');
    }
}
