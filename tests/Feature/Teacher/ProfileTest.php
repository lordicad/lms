<?php

namespace Tests\Feature\Teacher;

use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What a teacher may change is covered by ProfileLockedFieldsTest. This is about the page rendering
 * for the states a real roster contains.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_renders_for_a_teacher(): void
    {
        $teacher = User::factory()->teacher()->create(['school_id' => School::factory()->create()->id]);
        $teacher->subjects()->sync([Subject::factory()->create()->id]);

        $this->actingAs($teacher)->get(route('profile.edit'))->assertOk();
    }

    /**
     * A freshly created account has no school, no position, no subjects and no homeroom class. The
     * page now reads those straight off the model to display them, so every one of them is a null
     * the view has to survive.
     */
    public function test_profile_page_renders_before_the_admin_has_filled_anything_in(): void
    {
        $teacher = User::factory()->teacher()->create([
            'school_id' => null,
            'position' => null,
            'phone' => null,
        ]);

        $this->actingAs($teacher)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee(__('Belum ditetapkan'))
            ->assertSee(__('Bukan guru kelas'));
    }
}
