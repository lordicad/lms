<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Puts every admin who has no school yet in charge of one.
 *
 * An admin oversees a single school, and the scoping fails closed — no school means empty pages —
 * so an account left unassigned would simply see nothing. This gives the existing administrator a
 * school to run. Admins who already have one are left alone.
 */
class AdminSchoolSeeder extends Seeder
{
    private const DEFAULT_SCHOOL = 'Bukit Damansara';

    public function run(): void
    {
        $school = School::where('name', 'like', '%'.self::DEFAULT_SCHOOL.'%')->first()
            ?? School::orderBy('id')->first();

        if (! $school) {
            $this->command?->warn('No schools exist — run SchoolSeeder first. Nothing was changed.');

            return;
        }

        $assigned = User::where('role', User::ROLE_ADMIN)
            ->whereNull('school_id')
            ->update(['school_id' => $school->id]);

        $this->command?->info("Admins put in charge of {$school->name}: {$assigned}");
    }
}
