<?php

namespace App\Mail;

use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The one-time code for a forgot-password request. For a student this goes to the guardian's email,
 * for a teacher to their own — whoever the account trusts to receive its sign-in details. The code
 * is passed in plain (it is never stored in plain form) and is valid for a few minutes only.
 */
class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $account,
        public string $otp,
        /** Set when the message goes to a guardian rather than the account holder. */
        public ?string $guardianName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Kod pengesahan set semula kata laluan WeLearn'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.password-otp',
            with: [
                'account' => $this->account,
                'otp' => $this->otp,
                'guardianName' => $this->guardianName,
                'minutes' => PasswordOtp::TTL_MINUTES,
            ],
        );
    }
}
