<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtpMail;
use App\Models\AdminNotification;
use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The forgot-password OTP flow, in two steps that the login page drives over fetch:
 *
 *   send()   - look up the account, email a one-time code to the address it trusts (a teacher's
 *              own email, a student's guardian's), and remember only the hash.
 *   verify() - check the code, and on success raise a request in the school admins' bell. The admin
 *              does the actual reset (see AdminUserController); the OTP only proves the requester can
 *              read the account's email, so a stranger cannot trigger a reset for someone else.
 *
 * Both return JSON. Errors are specific by design (a private school tool, not a public site): the
 * requester is told plainly when no account or no email is found, rather than a vague catch-all.
 */
class ForgotPasswordOtpController extends Controller
{
    /** Step 1: issue and email a fresh code. */
    public function send(Request $request): JsonResponse
    {
        $login = trim((string) $request->input('login', ''));

        if ($login === '') {
            return $this->fail(__('Sila masukkan nama pengguna atau emel anda.'));
        }

        [$user, $error] = $this->resolve($login);
        if ($error) {
            return $this->fail($error);
        }

        // Where the code goes: a student can't be reached directly, so it goes to the guardian.
        $isGuardian = $user->isStudent();
        $target = $isGuardian ? $user->guardian_email : $user->email;

        if (! filled($target)) {
            return $this->fail($isGuardian
                ? __('Tiada emel penjaga pada rekod akaun ini. Sila hubungi admin sekolah.')
                : __('Tiada emel pada rekod akaun ini. Sila hubungi admin sekolah.'));
        }

        // Resend cooldown: block a fresh code while the last one is still young.
        $latest = PasswordOtp::where('user_id', $user->id)->latest('id')->first();
        if ($latest && $latest->created_at->gt(Carbon::now()->subSeconds(PasswordOtp::RESEND_COOLDOWN_SECONDS))) {
            return $this->fail(__('Sila tunggu sebentar sebelum meminta kod baharu.'));
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // One live code per account: clear any previous ones before issuing this.
        PasswordOtp::where('user_id', $user->id)->delete();
        PasswordOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'sent_to' => $target,
            'expires_at' => PasswordOtp::expiryFromNow(),
        ]);

        // A mail failure must not leak whether the address exists or half-complete the flow; it is
        // logged and swallowed, exactly like the account-credentials mail.
        try {
            Mail::to($target)->send(new PasswordResetOtpMail($user, $otp, $isGuardian ? $user->guardian_name : null));
        } catch (\Throwable $e) {
            Log::error('Password OTP mail failed', ['user' => $user->id, 'error' => $e->getMessage()]);
        }

        return response()->json([
            'ok' => true,
            'hint' => $isGuardian
                ? __('Kod telah dihantar ke emel penjaga (:email).', ['email' => $this->mask($target)])
                : __('Kod telah dihantar ke emel anda (:email).', ['email' => $this->mask($target)]),
        ]);
    }

    /** Step 2: check the code and, if right, raise the admin request. */
    public function verify(Request $request): JsonResponse
    {
        $login = trim((string) $request->input('login', ''));
        $otp = trim((string) $request->input('otp', ''));

        if ($login === '' || $otp === '') {
            return $this->fail(__('Sila masukkan kod pengesahan.'));
        }

        [$user, $error] = $this->resolve($login);
        if ($error) {
            return $this->fail($error);
        }

        $record = PasswordOtp::where('user_id', $user->id)->whereNull('consumed_at')->latest('id')->first();

        if (! $record) {
            return $this->fail(__('Sila minta kod terlebih dahulu.'));
        }

        if ($record->isExpired()) {
            return $this->fail(__('Kod telah tamat tempoh. Sila minta kod baharu.'));
        }

        if ($record->attempts >= PasswordOtp::MAX_ATTEMPTS) {
            return $this->fail(__('Terlalu banyak percubaan. Sila minta kod baharu.'));
        }

        if (! Hash::check($otp, $record->otp_hash)) {
            $record->increment('attempts');

            return $this->fail(__('Kod tidak sah. Sila cuba lagi.'));
        }

        $record->forceFill(['consumed_at' => Carbon::now()])->save();

        // Raise it for the school's admins. A user with no school produces an unscoped notification
        // (school_id null) that every admin can see, so it is never lost.
        AdminNotification::record(
            $user->school_id,
            AdminNotification::TYPE_PASSWORD_RESET,
            $user->name,
            '@'.$user->username,
            route('admin.pengguna.edit', $user),
        );

        return response()->json([
            'ok' => true,
            'message' => __('Pengesahan berjaya. Admin sekolah anda telah dimaklumkan.'),
        ]);
    }

    /**
     * Find the account behind a username-or-email entry. Email is unique so it wins; usernames are
     * not unique across schools, so a colliding username is refused with a nudge to use the email.
     *
     * @return array{0: ?User, 1: ?string}  [user, errorMessage]
     */
    private function resolve(string $login): array
    {
        if ($byEmail = User::where('email', $login)->first()) {
            return [$byEmail, null];
        }

        $byUsername = User::where('username', $login)->get();

        if ($byUsername->count() === 1) {
            return [$byUsername->first(), null];
        }

        if ($byUsername->count() > 1) {
            return [null, __('Beberapa akaun menggunakan nama pengguna itu. Sila masukkan emel anda.')];
        }

        return [null, __('Tiada akaun ditemui dengan nama pengguna atau emel itu.')];
    }

    /** Partly hide an email for the on-screen confirmation: "a•••n@gmail.com". */
    private function mask(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($domain === '') {
            return $email;
        }

        $shown = mb_strlen($name) <= 2
            ? mb_substr($name, 0, 1)
            : mb_substr($name, 0, 1).'•••'.mb_substr($name, -1);

        return $shown.'@'.$domain;
    }

    private function fail(string $message): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => $message], 422);
    }
}
