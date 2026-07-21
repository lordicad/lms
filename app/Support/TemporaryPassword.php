<?php

namespace App\Support;

/**
 * A readable one-time password for a newly created account.
 *
 * It gets read off a screen, written on a slip of paper, or typed out of a WhatsApp message by a
 * parent — often for a seven year old — so it is built to be said aloud and typed correctly rather
 * than to be maximally random. Pronounceable syllables, no characters that read as each other
 * (0/O, 1/l/I), and a short digit tail.
 *
 * The trade-off is safe because the password is temporary by construction: EnsurePasswordChanged
 * makes the owner replace it the first time they sign in, so it is only ever valid for one login.
 */
class TemporaryPassword
{
    /** No 'c' (reads as s/k), no 'q'/'x'/'v'/'w' (awkward in Malay), no 'l' (reads as 1). */
    private const CONSONANTS = 'bdghjkmnprsty';

    private const VOWELS = 'aeiou';

    /** Three syllables and four digits, e.g. "makuri-4821". */
    public static function generate(): string
    {
        $word = '';

        for ($i = 0; $i < 3; $i++) {
            $word .= self::CONSONANTS[random_int(0, strlen(self::CONSONANTS) - 1)];
            $word .= self::VOWELS[random_int(0, strlen(self::VOWELS) - 1)];
        }

        return $word.'-'.random_int(1000, 9999);
    }
}
