<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_run_and_a_user_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_landing_page_loads(): void
    {
        $this->get('/')->assertOk();
    }

    /**
     * The landing background points at a real file.
     *
     * A CSS `url()` to a missing image fails silently — the page still returns 200 and just shows
     * the fallback colour — so the only way to catch a moved or misnamed file is to check the page
     * names it and the file is actually there.
     */
    public function test_the_landing_background_image_exists(): void
    {
        $this->get('/')->assertOk()->assertSee('images/LandingPic.png');

        $this->assertFileExists(public_path('images/LandingPic.png'));
    }
}
