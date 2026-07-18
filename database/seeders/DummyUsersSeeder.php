<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Dummy accounts for demoing the admin dashboards: 7 teachers per subject (189) and 100 students
 * per Tahun (600).
 *
 * Everything here is deterministic — names come from fixed pools indexed by position, so the exact
 * set of emails can be regenerated for the credential hand-off without reading the database. Teacher
 * and student first-name pools are disjoint, so no two generated usernames ever collide.
 *
 * The two shared passwords are hashed once each and reused across every account, so seeding 789 rows
 * costs two bcrypt calls rather than 789 — the difference between a migration that finishes in a
 * second and one that runs for three minutes and risks a deploy timeout.
 *
 * insertOrIgnore makes it safe to run twice: a re-run skips rows whose email/username already exist
 * rather than erroring or duplicating.
 */
class DummyUsersSeeder extends Seeder
{
    public const TEACHER_DOMAIN = 'moe.gov.my';

    public const STUDENT_DOMAIN = 'moe.edu.my';

    public const TEACHER_PASSWORD = 'Teacher@123';

    public const STUDENT_PASSWORD = 'Student@123';

    public const TEACHERS_PER_SUBJECT = 7;

    public const STUDENTS_PER_GRADE = 100;

    /** Adult first names for teachers. Disjoint from STUDENT_FIRST so usernames never clash. */
    private const TEACHER_FIRST = [
        'Aminah', 'Rahim', 'Zainab', 'Hassan', 'Faridah', 'Kamal', 'Noraini', 'Suhaimi', 'Rohana',
        'Azman', 'Salmah', 'Ismail', 'Halimah', 'Zulkifli', 'Mariam', 'Ramli', 'Latifah', 'Shukri',
        'Rosnah', 'Bakri', 'Kalsom', 'Idris', 'Junaidi', 'Habibah', 'Othman', 'Roslan', 'Zaiton',
        'Malik', 'Norliza', 'Sharifah',
    ];

    /** Child first names for students. */
    private const STUDENT_FIRST = [
        'Adam', 'Aisyah', 'Harith', 'Meiling', 'Danial', 'Nurul', 'Iman', 'Aqil', 'Sofea', 'Hakim',
        'Balqis', 'Zikri', 'Alia', 'Irfan', 'Hana', 'Amir', 'Qistina', 'Ryan', 'Chloe', 'Weijie',
        'Meixin', 'Junhao', 'Arjun', 'Divya', 'Kavya', 'Ravi', 'Xinyi', 'Ashwin', 'Farah', 'Aina',
        'Ezra', 'Lucas', 'Nadia', 'Yusuf', 'Zara', 'Emir', 'Batrisyia', 'Haziq', 'Dania', 'Elyssa',
    ];

    /** Shared surnames, across the main ethnic groups. */
    private const LAST = [
        'Yusof', 'Abdullah', 'Ismail', 'Rahman', 'Hashim', 'Bakar', 'Osman', 'Karim', 'Salleh',
        'Aziz', 'Ibrahim', 'Zainal', 'Hamid', 'Latif', 'Razak', 'Mansor', 'Halim', 'Jamal',
        'Rashid', 'Idris', 'Tan', 'Lim', 'Wong', 'Lee', 'Ng', 'Chan', 'Goh', 'Teoh', 'Raj',
        'Kumar', 'Nair', 'Menon', 'Pillai', 'Singh', 'Das', 'Reddy', 'Suppiah', 'Krishnan',
        'Balan', 'Maniam',
    ];

    public function run(): void
    {
        $rows = self::rows();
        $now = now();

        $teacherHash = Hash::make(self::TEACHER_PASSWORD);
        $studentHash = Hash::make(self::STUDENT_PASSWORD);

        $records = [];

        foreach ($rows['teachers'] as $t) {
            $records[] = [
                'name' => $t['name'],
                'username' => $t['username'],
                'email' => $t['email'],
                'password' => $teacherHash,
                'role' => User::ROLE_TEACHER,
                'is_active' => true,
                'grade_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // grade level -> id, so a student row can carry its real grade_id.
        $gradeId = Grade::pluck('id', 'level');

        foreach ($rows['students'] as $s) {
            $records[] = [
                'name' => $s['name'],
                'username' => $s['username'],
                'email' => $s['email'],
                'password' => $studentHash,
                'role' => User::ROLE_STUDENT,
                'is_active' => true,
                'grade_id' => $gradeId[$s['grade']] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Chunked so the query never grows past what the driver will accept, insertOrIgnore so a
        // second run (or an email that already exists) is skipped rather than fatal.
        foreach (array_chunk($records, 200) as $chunk) {
            DB::table('users')->insertOrIgnore($chunk);
        }
    }

    /**
     * The full generated identity set, without passwords. Pure and deterministic, so the credential
     * export and the insert build from exactly the same emails.
     *
     * @return array{teachers: array<int, array{name:string, username:string, email:string, subject_index:int}>, students: array<int, array{name:string, username:string, email:string, grade:int}>}
     */
    public static function rows(): array
    {
        $subjectCount = 27; // Kurikulum 2027 subject count; 7 teachers each = 189.
        $teacherTotal = $subjectCount * self::TEACHERS_PER_SUBJECT;

        $teachers = [];
        for ($k = 0; $k < $teacherTotal; $k++) {
            $first = self::TEACHER_FIRST[$k % count(self::TEACHER_FIRST)];
            $last = self::LAST[intdiv($k, count(self::TEACHER_FIRST)) % count(self::LAST)];
            $local = strtolower("{$first}.{$last}");

            $teachers[] = [
                'name' => "{$first} {$last}",
                'username' => $local,
                'email' => "{$local}@".self::TEACHER_DOMAIN,
                // Which subject (0..26) this teacher is notionally for. Not stored — bare accounts —
                // but kept so the credential list can group them if ever wanted.
                'subject_index' => intdiv($k, self::TEACHERS_PER_SUBJECT),
            ];
        }

        $students = [];
        $k = 0;
        for ($level = 1; $level <= 6; $level++) {
            for ($n = 0; $n < self::STUDENTS_PER_GRADE; $n++, $k++) {
                $first = self::STUDENT_FIRST[$k % count(self::STUDENT_FIRST)];
                $last = self::LAST[intdiv($k, count(self::STUDENT_FIRST)) % count(self::LAST)];
                $local = strtolower("{$first}.{$last}");

                $students[] = [
                    'name' => "{$first} {$last}",
                    'username' => $local,
                    'email' => "{$local}@".self::STUDENT_DOMAIN,
                    'grade' => $level,
                ];
            }
        }

        return ['teachers' => $teachers, 'students' => $students];
    }
}
