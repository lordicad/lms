<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\User;
use Database\Seeders\DemoStudentEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoStudentEmailTest extends TestCase
{
    use RefreshDatabase;

    private Grade $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grade = Grade::factory()->level(3)->create();
    }

    private function student(string $name, string $email): User
    {
        return User::factory()->student($this->grade->level)->create(['name' => $name, 'email' => $email]);
    }

    public function test_the_address_is_built_from_the_students_name(): void
    {
        $student = $this->student('Zara Idris', 'rankdemo.6.99@moe.edu.my');

        $this->seed(DemoStudentEmailSeeder::class);

        $this->assertSame('zara.idris@moe.edu.my', $student->fresh()->email);
    }

    /** "bin" and "binti" are relationship words, not part of what someone is called. */
    public function test_particles_are_dropped_from_the_handle(): void
    {
        $student = $this->student('Nurul Ain Binti Khairuddin', 'rankdemo.1.1@moe.edu.my');

        $this->seed(DemoStudentEmailSeeder::class);

        $this->assertSame('nurul.ain.khairuddin@moe.edu.my', $student->fresh()->email);
    }

    /** Email is the sign-in identifier, so two students of the same name cannot share one. */
    public function test_a_repeated_name_gets_a_numeric_suffix(): void
    {
        $first = $this->student('Adam Abdullah', 'rankdemo.1.2@moe.edu.my');
        $second = $this->student('Adam Abdullah', 'rankdemo.1.3@moe.edu.my');

        $this->seed(DemoStudentEmailSeeder::class);

        $emails = [$first->fresh()->email, $second->fresh()->email];
        sort($emails);

        $this->assertSame(['adam.abdullah2@moe.edu.my', 'adam.abdullah@moe.edu.my'], $emails);
        $this->assertCount(2, array_unique($emails));
    }

    /** A real address someone typed must survive: only the demo domain is in scope. */
    public function test_an_address_outside_the_demo_domain_is_left_alone(): void
    {
        $real = $this->student('Hasyimah Binti Chew', 'sofilina8888@gmail.com');

        $this->seed(DemoStudentEmailSeeder::class);

        $this->assertSame('sofilina8888@gmail.com', $real->fresh()->email);
    }

    public function test_it_never_takes_an_address_another_account_already_holds(): void
    {
        $teacher = User::factory()->teacher()->create(['email' => 'adam.abdullah@moe.edu.my']);
        $student = $this->student('Adam Abdullah', 'rankdemo.1.4@moe.edu.my');

        $this->seed(DemoStudentEmailSeeder::class);

        $this->assertSame('adam.abdullah@moe.edu.my', $teacher->fresh()->email);
        $this->assertSame('adam.abdullah2@moe.edu.my', $student->fresh()->email);
    }

    public function test_running_it_twice_changes_nothing_the_second_time(): void
    {
        $this->student('Zara Idris', 'rankdemo.6.99@moe.edu.my');
        $this->student('Zara Idris', 'rankdemo.6.98@moe.edu.my');

        $this->seed(DemoStudentEmailSeeder::class);
        $after = User::orderBy('id')->pluck('email', 'id');

        $this->seed(DemoStudentEmailSeeder::class);

        $this->assertEquals($after, User::orderBy('id')->pluck('email', 'id'));
    }
}
