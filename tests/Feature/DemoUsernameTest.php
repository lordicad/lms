<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoUsernameSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoUsernameTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(string $name, string $username): User
    {
        return User::factory()->teacher()->create(['name' => $name, 'username' => $username]);
    }

    public function test_a_dotted_username_becomes_the_capitalised_first_name(): void
    {
        $user = $this->teacher('Rohana Osman', 'rohana.osman');

        $this->seed(DemoUsernameSeeder::class);

        $this->assertSame('Rohana', $user->fresh()->username);
    }

    public function test_a_numbered_username_is_taken_from_the_name_not_the_handle(): void
    {
        $user = $this->teacher('Zara Idris', 'rankdemo.6.99');

        $this->seed(DemoUsernameSeeder::class);

        $this->assertSame('Zara', $user->fresh()->username);
    }

    /** "Cikgu Rahimah Yusof" should give Rahimah — the title is not her name. */
    public function test_a_leading_honorific_is_skipped(): void
    {
        $user = $this->teacher('Cikgu Rahimah Yusof', 'cikgu.demo');

        $this->seed(DemoUsernameSeeder::class);

        $this->assertSame('Rahimah', $user->fresh()->username);
    }

    /** A sensible nickname only wants a capital; it must not be re-derived from the full name. */
    public function test_a_lowercase_nickname_is_capitalised_in_place(): void
    {
        $user = $this->teacher('Muhammad Harith Danial', 'harith');

        $this->seed(DemoUsernameSeeder::class);

        $this->assertSame('Harith', $user->fresh()->username);
    }

    public function test_a_username_someone_typed_deliberately_is_left_alone(): void
    {
        $chosen = $this->teacher('Rohana Osman', 'Cikgu Ana');
        $plain = $this->teacher('Hasyimah Binti Chew', 'Hasyimah');

        $this->seed(DemoUsernameSeeder::class);

        $this->assertSame('Cikgu Ana', $chosen->fresh()->username);
        $this->assertSame('Hasyimah', $plain->fresh()->username);
    }

    public function test_running_it_twice_changes_nothing_the_second_time(): void
    {
        $this->teacher('Rohana Osman', 'rohana.osman');
        $this->teacher('Muhammad Harith Danial', 'harith');

        $this->seed(DemoUsernameSeeder::class);
        $after = User::orderBy('id')->pluck('username', 'id');

        $this->seed(DemoUsernameSeeder::class);

        $this->assertEquals($after, User::orderBy('id')->pluck('username', 'id'));
    }
}
