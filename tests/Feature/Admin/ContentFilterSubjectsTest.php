<?php

namespace Tests\Feature\Admin;

use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContentFilterSubjectsTest extends TestCase
{
    use RefreshDatabase;

    private function offer(Subject $subject, Grade $grade): void
    {
        DB::table('grade_subject')->insertOrIgnore([
            'subject_id' => $subject->id,
            'grade_id' => $grade->id,
        ]);
    }

    public function test_admin_content_subject_list_follows_the_selected_year(): void
    {
        $admin = User::factory()->admin()->create();
        $grade = Grade::factory()->level(1)->create();

        $offered = Subject::factory()->create(['name' => 'Matematik Tahun Satu']);
        $notOffered = Subject::factory()->create(['name' => 'Sejarah Tingkatan Lima']);
        $this->offer($offered, $grade);

        $response = $this->actingAs($admin)->get(route('admin.kandungan.video', ['tahun' => 1]));

        $response->assertOk();
        $response->assertSee('Matematik Tahun Satu');
        $response->assertDontSee('Sejarah Tingkatan Lima');
    }

    public function test_admin_content_lists_all_subjects_when_no_year_is_chosen(): void
    {
        $admin = User::factory()->admin()->create();
        $grade = Grade::factory()->level(1)->create();

        $a = Subject::factory()->create(['name' => 'Subjek Alfa']);
        $b = Subject::factory()->create(['name' => 'Subjek Beta']);
        $this->offer($a, $grade);

        $response = $this->actingAs($admin)->get(route('admin.kandungan.video'));

        $response->assertOk();
        $response->assertSee('Subjek Alfa');
        $response->assertSee('Subjek Beta');
    }

    public function test_talent_page_drops_a_subject_not_offered_in_the_selected_year(): void
    {
        $admin = User::factory()->admin()->create();
        $grade = Grade::factory()->level(2)->create();
        $subject = Subject::factory()->create(['name' => 'Subjek Luar Tahun']);
        // Deliberately NOT offered in Tahun 2.

        $response = $this->actingAs($admin)->get(route('admin.bakat', [
            'tahun' => 2,
            'subjek' => $subject->slug,
        ]));

        $response->assertOk();
        // The controller validates the pair, so the invalid subject is dropped from the selection.
        $this->assertNull($response->viewData('subjectSlug'));
    }
}
