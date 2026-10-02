<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * The mobile API login must lock out after repeated failures, exactly like the web login.
 * Before this, /api/auth/login had no limit at all, so it was a way around the web lockout.
 */
class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('murid@example.test|127.0.0.1');
    }

    private function attempt(string $password)
    {
        return $this->postJson('/api/auth/login', [
            'login' => 'murid@example.test',
            'password' => $password,
            'device_name' => 'test',
        ]);
    }

    public function test_the_correct_password_still_signs_in(): void
    {
        User::factory()->student(4)->create(['email' => 'murid@example.test', 'password' => 'betul123']);

        $this->attempt('betul123')->assertOk()->assertJsonStructure(['token']);
    }

    public function test_five_wrong_passwords_lock_the_account_out_even_for_the_right_one(): void
    {
        User::factory()->student(4)->create(['email' => 'murid@example.test', 'password' => 'betul123']);

        for ($i = 0; $i < 5; $i++) {
            $this->attempt('salah')->assertStatus(422);
        }

        $this->attempt('betul123')->assertStatus(429);
    }

    public function test_a_successful_sign_in_resets_the_counter(): void
    {
        User::factory()->student(4)->create(['email' => 'murid@example.test', 'password' => 'betul123']);

        for ($i = 0; $i < 4; $i++) {
            $this->attempt('salah')->assertStatus(422);
        }

        $this->attempt('betul123')->assertOk();
        $this->attempt('salah')->assertStatus(422); // counter started again, not locked
    }
}
