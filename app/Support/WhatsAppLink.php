<?php

namespace App\Support;

use App\Models\User;

/**
 * Builds a click-to-send wa.me link for handing new sign-in details to a guardian.
 *
 * Sending a WhatsApp message from the server needs a paid Business API account, so this takes the
 * other route: the admin gets a link that opens WhatsApp with the recipient and the whole message
 * already filled in, and presses send. No API credentials, and nothing leaves the server.
 */
class WhatsAppLink
{
    /** Malaysia. Local numbers are stored as 01x-xxx xxxx, so the leading 0 is swapped for this. */
    public const COUNTRY_CODE = '60';

    /**
     * Normalise a stored phone number to the digits-only international form wa.me expects.
     * Returns null when there is nothing usable to dial.
     */
    public static function normalise(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        // A local number ("012-345 6789" / "6012...") — swap the trunk 0 for the country code.
        if (str_starts_with($digits, '0')) {
            $digits = self::COUNTRY_CODE.substr($digits, 1);
        }

        // Too short to be a real number once punctuation is gone.
        return strlen($digits) >= 10 ? $digits : null;
    }

    /**
     * The full wa.me URL, or null when the number is unusable.
     */
    public static function for(User $account, string $plainPassword, ?string $guardianName = null): ?string
    {
        $number = self::normalise($account->guardian_phone);

        if (! $number) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode(self::message($account, $plainPassword, $guardianName));
    }

    /** The pre-filled message body. Mirrors the email so both channels say the same thing. */
    public static function message(User $account, string $plainPassword, ?string $guardianName = null): string
    {
        $greeting = $guardianName
            ? __('Salam sejahtera :name,', ['name' => $guardianName])
            : __('Salam sejahtera,');

        return implode("\n", [
            $greeting,
            '',
            __('Akaun WeLearn telah dibuka untuk :name.', ['name' => $account->name]),
            '',
            // Whichever identifier actually signs this account in — email for a teacher,
            // username for a student, who usually has no email of their own.
            ($account->signsInWithEmail() ? __('Emel') : __('Nama pengguna')).': '.$account->signInIdentifier(),
            __('Kata laluan sementara').': '.$plainPassword,
            '',
            __('Log masuk di').': '.route('login'),
            '',
            __('Kata laluan di atas adalah sementara. Pada log masuk pertama, anda akan diminta menetapkan kata laluan sendiri. Nama pengguna kekal sama.'),
        ]);
    }
}
