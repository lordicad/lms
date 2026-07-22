<?php

namespace Tests\Feature\Admin;

use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentClassFilterTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Grade $year3;

    private Grade $year4;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->year3 = Grade::factory()->level(3)->create();
        $this->year4 = Grade::factory()->level(4)->create();
        $this->admin = User::factory()->admin()->create(['school_id' => $this->school->id]);
    }

    private function classIn(Grade $grade, string $name): SchoolClass
    {
        return SchoolClass::factory()->for($this->school)->state([
            'grade_id' => $grade->id, 'name' => $name,
        ])->create();
    }

    private function studentIn(SchoolClass $class, Grade $grade, string $name): User
    {
        return User::factory()->student($grade->level)->create([
            'school_id' => $this->school->id,
            'school_class_id' => $class->id,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@moe.edu.my',
        ]);
    }

    public function test_choosing_a_class_narrows_the_list_to_it(): void
    {
        $bestari = $this->classIn($this->year3, 'Bestari');
        $cerdik = $this->classIn($this->year3, 'Cerdik');
        $this->studentIn($bestari, $this->year3, 'Murid Bestari');
        $this->studentIn($cerdik, $this->year3, 'Murid Cerdik');

        $this->actingAs($this->admin)->get(route('admin.murid', ['kelas' => $bestari->id]))
            ->assertOk()
            ->assertSee('Murid Bestari')
            ->assertDontSee('Murid Cerdik');
    }

    /** A class belongs to a year, so the list follows the Tahun on screen. */
    public function test_the_class_list_follows_the_chosen_year(): void
    {
        $this->classIn($this->year3, 'Kelas Tahun Tiga');
        $this->classIn($this->year4, 'Kelas Tahun Empat');

        $classes = $this->actingAs($this->admin)->get(route('admin.murid', ['tahun' => 3]))
            ->assertOk()
            ->viewData('classes');

        $this->assertSame(['Kelas Tahun Tiga'], $classes->pluck('name')->all());
    }

    /** Switching year must not leave a class from the old year filtering invisibly. */
    public function test_a_class_outside_the_chosen_year_is_ignored(): void
    {
        $year4Class = $this->classIn($this->year4, 'Cerdik');
        $this->studentIn($this->classIn($this->year3, 'Bestari'), $this->year3, 'Murid Tahun Tiga');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.murid', ['tahun' => 3, 'kelas' => $year4Class->id]));

        $response->assertOk();
        $this->assertNull($response->viewData('classId'));
        $response->assertSee('Murid Tahun Tiga');
    }

    /** Classes are school property, so another school's never appear. */
    public function test_another_schools_classes_are_not_offered(): void
    {
        $this->classIn($this->year3, 'Kelas Sini');
        $elsewhere = School::factory()->create();
        SchoolClass::factory()->for($elsewhere)->state([
            'grade_id' => $this->year3->id, 'name' => 'Kelas Sana',
        ])->create();

        $classes = $this->actingAs($this->admin)->get(route('admin.murid'))
            ->assertOk()
            ->viewData('classes');

        $this->assertSame(['Kelas Sini'], $classes->pluck('name')->all());
    }
}
