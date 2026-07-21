<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Rewrites the demo students' sign-in addresses to read as their own name —
 * "rankdemo.6.99@moe.edu.my" becomes "zara.idris@moe.edu.my".
 *
 * Email is the sign-in identifier and is unique, which shapes two decisions:
 *
 *  - The address is built from the full name, not the first name. Only 72 distinct first names
 *    cover 1,394 accounts, so "zara@" would collide almost immediately.
 *  - Full names repeat too (270 of them), so a numeric suffix is added only where one is needed:
 *    the first Adam Yusof keeps adam.yusof@, the second becomes adam.yusof2@.
 *
 * Scope is limited to students already on the demo domain, so a real address an admin typed —
 * anything on gmail.com, moe.gov.my or elsewhere — is never touched.
 */
class DemoStudentEmailSeeder extends Seeder
{
    private const DOMAIN = 'moe.edu.my';

    /** Dropped from the handle: they are relationship words, not part of what someone is called. */
    private const PARTICLES = ['bin', 'binti', 'bt', 'bte', 'al', 'ap', 'a/l', 'a/p', 'anak'];

    public function run(): void
    {
        // Every address in use, so a generated one can never land on top of another account.
        $taken = User::whereNotNull('email')->pluck('email')->map('strtolower')->flip();

        $renamed = 0;
        $kept = 0;

        User::where('role', User::ROLE_STUDENT)
            ->where('email', 'like', '%@'.self::DOMAIN)
            ->orderBy('id')
            ->chunkById(200, function ($students) use (&$taken, &$renamed, &$kept) {
                foreach ($students as $student) {
                    $base = $this->handleFrom((string) $student->name);

                    if ($base === '') {
                        $kept++;

                        continue;
                    }

                    // Already in the right shape (base@ or base<n>@)? Leave it, so re-running is a
                    // no-op rather than shuffling everyone onto a different number.
                    if (preg_match('/^'.preg_quote($base, '/').'\d*@'.preg_quote(self::DOMAIN, '/').'$/i', (string) $student->email)) {
                        $kept++;

                        continue;
                    }

                    $email = $this->firstFreeAddress($base, $taken);

                    $taken->forget(strtolower((string) $student->email));
                    $taken->put(strtolower($email), true);

                    $student->email = $email;
                    $student->save();
                    $renamed++;
                }
            });

        $this->command?->info("Student emails rewritten: {$renamed}");
        $this->command?->info("Already correct, left alone: {$kept}");
    }

    /** "Nurul Ain Binti Khairuddin" -> "nurul.ain.khairuddin". */
    private function handleFrom(string $name): string
    {
        $words = [];

        foreach (preg_split('/\s+/', trim($name)) ?: [] as $part) {
            $word = strtolower(preg_replace('/[^a-z]/i', '', $part) ?? '');

            if ($word === '' || in_array($word, self::PARTICLES, true)) {
                continue;
            }

            $words[] = $word;
        }

        return implode('.', $words);
    }

    /** base@domain, or base2@, base3@… — the first one nobody else holds. */
    private function firstFreeAddress(string $base, $taken): string
    {
        $candidate = $base.'@'.self::DOMAIN;
        $suffix = 1;

        while ($taken->has(strtolower($candidate))) {
            $suffix++;
            $candidate = $base.$suffix.'@'.self::DOMAIN;
        }

        return $candidate;
    }
}
