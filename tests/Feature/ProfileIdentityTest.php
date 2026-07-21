<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Email is the sign-in identifier and the admin owns it; username is a display nickname the owner
 * is free to change. The profile form has to enforce exactly that split.
 */
class ProfileIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_change_their_display_nickname(): void
    {
        $teacher = User::factory()->teacher()->create(['username' => 'ana']);

        $this->actingAs($teacher)->patch(route('profile.update'), [
            'name' => $teacher->name,
            'username' => 'Cikgu Ana',
            'email' => $teacher->email,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Cikgu Ana', $teacher->fresh()->username);
    }

    public function test_a_user_cannot_change_the_email_they_sign_in_with(): void
    {
        $teacher = User::factory()->teacher()->create(['email' => 'asal@moe.gov.my']);

        // Even posting a new address directly must not move the sign-in identifier.
        $this->actingAs($teacher)->patch(route('profile.update'), [
            'name' => $teacher->name,
            'username' => $teacher->username,
            'email' => 'cubaan-tukar@example.com',
        ])->assertSessionHasNoErrors();

        $this->assertSame('asal@moe.gov.my', $teacher->fresh()->email);
    }

    public function test_a_student_cannot_change_their_email_either(): void
    {
        $student = User::factory()->student(3)->create(['email' => 'murid@moe.gov.my']);

        $this->actingAs($student)->patch(route('profile.update'), [
            'name' => $student->name,
            'username' => 'Aisyah Comel',
            'email' => 'lain@example.com',
            'grade_level' => $student->grade->level,
        ])->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertSame('murid@moe.gov.my', $student->email);
        $this->assertSame('Aisyah Comel', $student->username);   // the nickname still changes
    }
}
