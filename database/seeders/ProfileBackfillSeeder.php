<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fills in the profile fields that were added after the demo accounts already existed — school,
 * phone, position and subjects for teachers; school, class and guardian details for students.
 *
 * Two rules make it safe to run against live data:
 *
 *  - It only ever writes a field that is currently empty. Anything a real admin has typed is left
 *    exactly as it is, so this can be run more than once without undoing their work.
 *  - Every value is derived from the account's own id, so a second run produces the same result
 *    rather than reshuffling everyone's details.
 */
class ProfileBackfillSeeder extends Seeder
{
    private const POSITIONS = [
        'Guru Akademik', 'Guru Akademik', 'Guru Akademik',   // weighted: most teachers are this
        'Guru Kanan Mata Pelajaran', 'Guru Penolong Kanan', 'Guru Bimbingan dan Kaunseling',
        'Guru Media', 'Guru Data', 'Guru Disiplin', 'Guru Prasekolah',
    ];

    private const GUARDIAN_FIRST = [
        'Ahmad', 'Siti', 'Mohd', 'Noraini', 'Kamal', 'Zainab', 'Hassan', 'Rohana', 'Faizal', 'Salmah',
        'Ramesh', 'Devi', 'Chong', 'Mei Fong', 'Suresh', 'Latha', 'Azman', 'Halimah', 'Yusri', 'Norma',
    ];

    private const GUARDIAN_LAST = [
        'bin Abdullah', 'binti Abdullah', 'bin Ismail', 'binti Ismail', 'a/l Muniandy', 'a/p Muniandy',
        'Tan', 'Lim', 'Wong', 'bin Osman', 'binti Osman', 'Kaur', 'Singh', 'bin Rahman', 'binti Rahman',
    ];

    /** Malaysian mobile prefixes, so the generated numbers look like real ones. */
    private const MOBILE_PREFIXES = ['012', '013', '014', '016', '017', '018', '019'];

    public function run(): void
    {
        $schools = School::orderBy('id')->get();

        if ($schools->isEmpty()) {
            $this->command?->warn('No schools found — run SchoolSeeder first. Nothing was changed.');

            return;
        }

        $this->backfillTeachers($schools);
        $this->backfillStudents($schools);
        $this->assignHomeroomTeachers();
    }

    private function backfillTeachers($schools): void
    {
        $subjects = Subject::orderBy('id')->get();
        $filled = 0;

        User::where('role', User::ROLE_TEACHER)->orderBy('id')->chunkById(100, function ($teachers) use ($schools, $subjects, &$filled) {
            foreach ($teachers as $teacher) {
                $touched = false;

                if (blank($teacher->school_id)) {
                    $teacher->school_id = $schools[$teacher->id % $schools->count()]->id;
                    $touched = true;
                }

                if (blank($teacher->phone)) {
                    $teacher->phone = $this->phoneFor($teacher->id);
                    $touched = true;
                }

                if (blank($teacher->position)) {
                    $teacher->position = self::POSITIONS[$teacher->id % count(self::POSITIONS)];
                    $touched = true;
                }

                if ($touched) {
                    $teacher->save();
                    $filled++;
                }

                $this->attachSubjects($teacher, $subjects);
            }
        });

        $this->command?->info("Teachers updated: {$filled}");
    }

    /**
     * A teacher's subjects are read from the content they have actually posted, so the profile
     * agrees with the library. Only teachers with nothing posted fall back to a derived pick.
     */
    private function attachSubjects(User $teacher, $subjects): void
    {
        if ($teacher->subjects()->exists() || $subjects->isEmpty()) {
            return;
        }

        $ids = DB::table('chapters')
            ->whereIn('chapters.id', function ($q) use ($teacher) {
                $q->select('chapter_id')->from('lessons')->where('teacher_id', $teacher->id)
                    ->union(DB::table('materials')->select('chapter_id')->where('teacher_id', $teacher->id))
                    ->union(DB::table('quizzes')->select('chapter_id')->where('teacher_id', $teacher->id));
            })
            ->distinct()
            ->pluck('subject_id')
            ->all();

        if ($ids === []) {
            // Nothing posted yet: give them one or two subjects so the profile is not blank.
            $ids = [$subjects[$teacher->id % $subjects->count()]->id];
            if ($teacher->id % 3 === 0) {
                $ids[] = $subjects[($teacher->id + 7) % $subjects->count()]->id;
            }
        }

        $teacher->subjects()->sync(array_unique($ids));
    }

    private function backfillStudents($schools): void
    {
        // Classes indexed by school+grade, so a student is only ever put in a class that belongs to
        // their own school and their own year — the same pairing the admin form enforces.
        $classes = SchoolClass::orderBy('id')->get()->groupBy(fn ($c) => $c->school_id.'-'.$c->grade_id);
        $filled = 0;

        User::where('role', User::ROLE_STUDENT)->orderBy('id')->chunkById(200, function ($students) use ($schools, $classes, &$filled) {
            foreach ($students as $student) {
                $touched = false;

                if (blank($student->school_id)) {
                    $student->school_id = $schools[$student->id % $schools->count()]->id;
                    $touched = true;
                }

                if (blank($student->school_class_id) && $student->grade_id) {
                    $options = $classes[$student->school_id.'-'.$student->grade_id] ?? collect();
                    if ($options->isNotEmpty()) {
                        $student->school_class_id = $options[$student->id % $options->count()]->id;
                        $touched = true;
                    }
                }

                if (blank($student->email) && filled($student->username)) {
                    $student->email = $student->username.'@moe.edu.my';
                    $touched = true;
                }

                $guardian = $this->guardianFor($student->id);

                if (blank($student->guardian_name)) {
                    $student->guardian_name = $guardian;
                    $touched = true;
                }

                if (blank($student->guardian_phone)) {
                    $student->guardian_phone = $this->phoneFor($student->id + 5000);
                    $touched = true;
                }

                if (blank($student->guardian_email)) {
                    $student->guardian_email = $this->guardianEmailFor($guardian, $student->id);
                    $touched = true;
                }

                if ($touched) {
                    $student->save();
                    $filled++;
                }
            }
        });

        $this->command?->info("Students updated: {$filled}");
    }

    /**
     * Give every class a homeroom teacher, drawn from the teachers of that same school. Classes
     * that already have one are left alone — homeroom_teacher_id is the single source of truth.
     */
    private function assignHomeroomTeachers(): void
    {
        $assigned = 0;

        foreach (SchoolClass::whereNull('homeroom_teacher_id')->orderBy('id')->get() as $class) {
            $candidates = User::where('role', User::ROLE_TEACHER)
                ->where('school_id', $class->school_id)
                // One class each: a teacher already running a homeroom is not offered another.
                ->whereDoesntHave('homeroomClass')
                ->orderBy('id')
                ->get();

            if ($candidates->isEmpty()) {
                continue;
            }

            $class->homeroom_teacher_id = $candidates[$class->id % $candidates->count()]->id;
            $class->save();
            $assigned++;
        }

        $this->command?->info("Homeroom teachers assigned: {$assigned}");
    }

    private function phoneFor(int $seed): string
    {
        $prefix = self::MOBILE_PREFIXES[$seed % count(self::MOBILE_PREFIXES)];
        $body = str_pad((string) (($seed * 7919) % 10000000), 7, '0', STR_PAD_LEFT);

        return $prefix.'-'.substr($body, 0, 3).' '.substr($body, 3);
    }

    private function guardianFor(int $seed): string
    {
        $first = self::GUARDIAN_FIRST[$seed % count(self::GUARDIAN_FIRST)];
        $last = self::GUARDIAN_LAST[intdiv($seed, count(self::GUARDIAN_FIRST)) % count(self::GUARDIAN_LAST)];

        return $first.' '.$last;
    }

    private function guardianEmailFor(string $guardianName, int $seed): string
    {
        $handle = strtolower(preg_replace('/[^a-z]+/i', '.', $guardianName));

        return trim($handle, '.').'.'.$seed.'@example.com';
    }
}
