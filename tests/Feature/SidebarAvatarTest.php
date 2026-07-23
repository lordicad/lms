<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The sidebar avatar shows the user's uploaded picture once they set one, falling back to their
 * initials when they have not. Covers all three shells — teacher, admin, student — since each keeps
 * its own copy of the sidebar.
 */
class SidebarAvatarTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, array{0: \Closure, 1: string}> */
    public static function shells(): array
    {
        return [
            'teacher' => [fn () => User::factory()->teacher()->create(), 'cikgu.dashboard'],
            'admin' => [fn () => User::factory()->admin()->create(), 'admin.dashboard'],
            'student' => [fn () => User::factory()->student(4)->create(['grade_id' => Grade::factory()->level(4)->create()->id]), 'belajar.index'],
        ];
    }

    /**
     * @dataProvider shells
     *
     * @param  \Closure(): User  $makeUser
     */
    public function test_the_sidebar_shows_the_uploaded_picture(\Closure $makeUser, string $route): void
    {
        $user = $makeUser();
        $user->update(['avatar' => 'avatars/me.jpg']);

        $html = $this->actingAs($user)->get(route($route))->assertOk()->getContent();

        $this->assertStringContainsString($user->avatarUrl(), $html, 'the sidebar does not show the uploaded avatar');
    }

    /**
     * @dataProvider shells
     *
     * @param  \Closure(): User  $makeUser
     */
    public function test_the_sidebar_falls_back_to_initials_without_a_picture(\Closure $makeUser, string $route): void
    {
        $user = $makeUser();
        $this->assertNull($user->avatar, 'expected no avatar for the fallback case');

        $html = $this->actingAs($user)->get(route($route))->assertOk()->getContent();

        $this->assertStringContainsString($user->initials(), $html, 'the sidebar lost the initials fallback');
    }
}
