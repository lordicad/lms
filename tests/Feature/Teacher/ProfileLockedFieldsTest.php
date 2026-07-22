<?php

namespace Tests\Feature\Teacher;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A teacher may change their display name, photo and phone number. Their legal name, position,
 * school, homeroom class and subjects taught are the school's record, kept by the admin.
 *
 * Hiding the inputs is not the control — a hand-written POST does not care what the form rendered —
 * so these assertions go through the route with the locked fields filled in and check the database
 * afterwards.
 */
class ProfileLockedFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(): User
    {
        $school = School::factory()->create(['name' => 'SK Bukit Damansara']);

        return User::factory()->teacher()->create([
            'name' => 'Aisyah Rahman',
            'username' => 'Aisyah',
            'position' => 'Guru Akademik',
            'school_id' => $school->id,
            'phone' => '013-000 7919',
        ]);
    }

    /** The three fields that are genuinely theirs. */
    public function test_a_teacher_can_change_their_username_and_phone(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher)
            ->patch(route('profile.update'), ['username' => 'Cikgu Aisyah', 'phone' => '012-345 6789'])
            ->assertRedirect();

        $teacher->refresh();

        $this->assertSame('Cikgu Aisyah', $teacher->username);
        $this->assertSame('012-345 6789', $teacher->phone);
    }

    public function test_a_posted_name_position_or_school_is_ignored(): void
    {
        $teacher = $this->teacher();
        $otherSchool = School::factory()->create(['name' => 'SK Taman Tun']);

        $this->actingAs($teacher)->patch(route('profile.update'), [
            'username' => 'Aisyah',
            'name' => 'Someone Else',
            'position' => 'Guru Besar',
            'school_id' => $otherSchool->id,
        ])->assertRedirect();

        $teacher->refresh();

        $this->assertSame('Aisyah Rahman', $teacher->name, 'the legal name is admin-maintained');
        $this->assertSame('Guru Akademik', $teacher->position, 'the position is admin-maintained');
        $this->assertSame('SK Bukit Damansara', $teacher->school->name, 'the school is admin-maintained');
    }

    public function test_posted_subjects_are_ignored(): void
    {
        $teacher = $this->teacher();
        $taught = Subject::factory()->create();
        $other = Subject::factory()->create();
        $teacher->subjects()->sync([$taught->id]);

        $this->actingAs($teacher)
            ->patch(route('profile.update'), ['username' => 'Aisyah', 'subjects' => [$other->id]])
            ->assertRedirect();

        $this->assertSame([$taught->id], $teacher->fresh()->subjects->pluck('id')->all());
    }

    /** Homeroom lives on the class row, so a teacher must not be able to claim one. */
    public function test_a_teacher_cannot_make_themselves_a_homeroom_teacher(): void
    {
        $teacher = $this->teacher();
        $class = SchoolClass::factory()->create([
            'school_id' => $teacher->school_id,
            'homeroom_teacher_id' => null,
        ]);

        $this->actingAs($teacher)
            ->patch(route('profile.update'), ['username' => 'Aisyah', 'homeroom_class_id' => $class->id])
            ->assertRedirect();

        $this->assertNull($class->fresh()->homeroom_teacher_id);
    }

    /** The page shows the record, and offers no way to submit a different one. */
    public function test_the_form_offers_no_input_for_the_locked_fields(): void
    {
        $html = $this->actingAs($this->teacher())->get(route('profile.edit'))->assertOk()->getContent();

        foreach (['name', 'position', 'school_id', 'homeroom_class_id', 'subjects[]'] as $field) {
            $this->assertDoesNotMatchRegularExpression(
                '/<(input|select|textarea)[^>]*name="'.preg_quote($field, '/').'"/',
                $html,
                "the teacher profile still submits a {$field} field",
            );
        }

        // Still legible on the page, just not editable.
        $this->assertStringContainsString('Aisyah Rahman', $html);
        $this->assertStringContainsString('Guru Akademik', $html);
        $this->assertStringContainsString('SK Bukit Damansara', $html);
    }

    /**
     * The read-only rows build their style by concatenating onto the shared $input string. Miss the
     * separating semicolon and you get "width:100%display:flex" — one invalid declaration that
     * takes both properties with it, so the value loses its centring and the box its width. Nothing
     * warns; it just looks slightly wrong.
     */
    public function test_the_read_only_rows_render_valid_css(): void
    {
        $html = $this->actingAs($this->teacher())->get(route('profile.edit'))->getContent();

        preg_match_all('/style="([^"]*)"/', $html, $styles);

        foreach ($styles[1] as $style) {
            $this->assertDoesNotMatchRegularExpression(
                // The digit is required: without it "place-items:" reads as em + "s" + ":".
                '/\d(%|px|em|rem|fr|vh|vw)[a-z-]+\s*:/',
                $style,
                "a declaration ran into the next one, so both are dropped: {$style}",
            );
        }
    }

    /**
     * The mobile app posts to its own endpoint with its own copy of these rules. It is the same
     * form, so it has to enforce the same thing — otherwise the web lock is only a suggestion.
     */
    public function test_the_mobile_endpoint_locks_the_same_fields(): void
    {
        $teacher = $this->teacher();
        $otherSchool = School::factory()->create(['name' => 'SK Taman Tun']);
        $subject = Subject::factory()->create();

        $this->actingAs($teacher, 'sanctum')->patchJson('/api/auth/profile', [
            'username' => 'Aisyah',
            'name' => 'Someone Else',
            'position' => 'Guru Besar',
            'school_id' => $otherSchool->id,
            'subjects' => [$subject->id],
        ])->assertOk();

        $teacher->refresh();

        $this->assertSame('Aisyah Rahman', $teacher->name);
        $this->assertSame('Guru Akademik', $teacher->position);
        $this->assertSame('SK Bukit Damansara', $teacher->school->name);
        $this->assertEmpty($teacher->subjects);
    }

    /** Students are unaffected: they still name themselves. */
    public function test_a_student_can_still_change_their_name(): void
    {
        $student = User::factory()->student(3)->create(['name' => 'Original']);

        $this->actingAs($student)->patch(route('profile.update'), [
            'name' => 'Nama Baharu',
            'username' => $student->username,
            'grade_level' => 3,
        ])->assertRedirect();

        $this->assertSame('Nama Baharu', $student->fresh()->name);
    }
}
