<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The sign-in details for a freshly created account, sent once at creation.
 *
 * The password is passed in rather than read from the model: it only exists in plain text for the
 * length of this request — the stored copy is a one-way hash — so this is the single moment it can
 * be delivered. It is a temporary password by design; EnsurePasswordChanged makes the owner replace
 * it the first time they sign in, which limits how long a password sitting in an inbox is worth
 * anything.
 */
class AccountCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $account,
        public string $plainPassword,
        /** Set when the message goes to a guardian rather than the account holder. */
        public ?string $guardianName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Akaun WeLearn anda telah dibuka'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.account-credentials',
            with: [
                'account' => $this->account,
                'plainPassword' => $this->plainPassword,
                'guardianName' => $this->guardianName,
                'loginUrl' => route('login'),
            ],
        );
    }
}
