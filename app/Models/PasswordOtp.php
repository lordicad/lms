<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A one-time forgot-password code. Only the hash is stored; the plain code is emailed once and
 * never kept. A row is single-use (consumed_at) and short-lived (expires_at), with an attempt
 * counter so a code cannot be brute-forced.
 */
class PasswordOtp extends Model
{
    /** How long a freshly issued code stays valid. */
    public const TTL_MINUTES = 10;

    /** Wrong guesses allowed before the code is dead and a new one must be requested. */
    public const MAX_ATTEMPTS = 5;

    /** Seconds a requester must wait before asking for another code. */
    public const RESEND_COOLDOWN_SECONDS = 60;

    protected $fillable = ['user_id', 'otp_hash', 'sent_to', 'expires_at', 'consumed_at', 'attempts'];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isUsable(): bool
    {
        return ! $this->isConsumed()
            && ! $this->isExpired()
            && $this->attempts < self::MAX_ATTEMPTS;
    }

    public static function expiryFromNow(): Carbon
    {
        return Carbon::now()->addMinutes(self::TTL_MINUTES);
    }
}
