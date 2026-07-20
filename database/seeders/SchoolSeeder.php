<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

/**
 * A small set of schools, each with two classes per Tahun, so the School / Class dropdowns on the
 * teacher and student profiles have something to choose. Idempotent (updateOrCreate on natural
 * keys), so it can run more than once without duplicating rows.
 */
class SchoolSeeder extends Seeder
{
    /** @var array<int, array{name: string, code: string, state: string}> */
    private const SCHOOLS = [
        ['name' => 'SK Taman Melati', 'code' => 'WBA0001', 'state' => 'Kuala Lumpur'],
        ['name' => 'SK Seri Bestari', 'code' => 'JBA0002', 'state' => 'Johor'],
        ['name' => 'SK Bukit Damansara', 'code' => 'BBA0003', 'state' => 'Selangor'],
        ['name' => 'SK Sungai Petani', 'code' => 'KBA0004', 'state' => 'Kedah'],
    ];

    private const CLASS_NAMES = ['Bestari', 'Cerdik'];

    public function run(): void
    {
        $grades = Grade::orderBy('level')->get();

        foreach (self::SCHOOLS as $data) {
            $school = School::updateOrCreate(['code' => $data['code']], [
                'name' => $data['name'],
                'state' => $data['state'],
            ]);

            foreach ($grades as $grade) {
                foreach (self::CLASS_NAMES as $name) {
                    SchoolClass::updateOrCreate(
                        ['school_id' => $school->id, 'grade_id' => $grade->id, 'name' => $name],
                        ['is_active' => true],
                    );
                }
            }
        }
    }
}
