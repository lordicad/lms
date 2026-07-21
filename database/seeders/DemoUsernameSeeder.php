<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Turns the machine-made usernames on the demo accounts ("rohana.osman", "rankdemo.6.99") into a
 * friendly first name ("Rohana", "Zara").
 *
 * Username is a display name — the dashboard greets people by it and it is deliberately not unique
 * — so a plain first name is what it should hold. Repeats are fine: every account signs in with its
 * email, so nothing depends on a username being distinct.
 *
 * What gets touched, and what does not:
 *
 *  - Contains a dot or a digit — the shape seeders and imports produce — so it is replaced with the
 *    first name taken from the account's full name.
 *  - Entirely lower case ("aisyah") — a sensible nickname that only wants a capital, so it is
 *    capitalised where it stands rather than re-derived, since "harith" should not become
 *    "Muhammad" just because that is the first word of the name on record.
 *  - Anything already carrying a capital ("Hasyimah", "Cikgu Ana") is read as deliberate and left
 *    alone.
 *
 * Running it twice is a no-op: everything it writes starts with a capital and holds neither a dot
 * nor a digit, so nothing matches on a second pass.
 */
class DemoUsernameSeeder extends Seeder
{
    /** Skipped when they lead a name, so "Cikgu Rahimah Yusof" gives Rahimah rather than Cikgu. */
    private const HONORIFICS = [
        'cikgu', 'puan', 'encik', 'tuan', 'dr', 'datuk', 'dato', 'datin', 'prof',
        'ustaz', 'ustazah', 'haji', 'hajah', 'mr', 'mrs', 'ms', 'sir', 'madam',
    ];

    /** The username column allows 3 to 30 characters; anything shorter is left as it was. */
    private const MIN_LENGTH = 3;

    public function run(): void
    {
        $renamed = 0;
        $skipped = 0;

        User::whereIn('role', [User::ROLE_TEACHER, User::ROLE_STUDENT])
            ->orderBy('id')
            ->chunkById(200, function ($users) use (&$renamed, &$skipped) {
                foreach ($users as $user) {
                    $current = (string) $user->username;

                    if (preg_match('/[.\d]/', $current)) {
                        // Machine-made ("rohana.osman", "rankdemo.6.99"): replace with a real name.
                        $wanted = $this->firstNameFrom((string) $user->name);
                    } elseif ($current !== '' && $current === mb_strtolower($current)) {
                        // Already a sensible nickname, only lower-cased ("aisyah"). Capitalise it
                        // rather than re-deriving: "harith" should not become "Muhammad".
                        $wanted = mb_strtoupper(mb_substr($current, 0, 1)).mb_substr($current, 1);
                    } else {
                        // Has a capital already, so someone typed it deliberately. Leave it.
                        continue;
                    }

                    if ($wanted === null || $wanted === $current) {
                        $skipped++;

                        continue;
                    }

                    $user->username = $wanted;
                    $user->save();
                    $renamed++;
                }
            });

        $this->command?->info("Usernames rewritten: {$renamed}");

        if ($skipped > 0) {
            $this->command?->warn("Left unchanged (no usable first name): {$skipped}");
        }
    }

    /** The first real word of a name, capitalised. Null when there is nothing usable to take. */
    private function firstNameFrom(string $name): ?string
    {
        foreach (preg_split('/\s+/', trim($name)) ?: [] as $part) {
            // Drop anything that is not a letter, apostrophe or hyphen: "Dato'" and "bin" survive
            // as words, while stray punctuation does not become part of the name.
            $word = preg_replace("/[^\p{L}'\-]/u", '', $part) ?? '';

            if ($word === '' || in_array(mb_strtolower(rtrim($word, "'")), self::HONORIFICS, true)) {
                continue;
            }

            if (mb_strlen($word) < self::MIN_LENGTH) {
                continue;
            }

            return mb_strtoupper(mb_substr($word, 0, 1)).mb_strtolower(mb_substr($word, 1));
        }

        return null;
    }
}
