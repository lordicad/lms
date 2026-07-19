<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_TEACHER = 'teacher';

    public const ROLE_STUDENT = 'student';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'is_active',
        'grade_id',
        'avatar',
        'locale',
        'theme',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'grade_id' => 'integer',
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Deactivated accounts cannot sign in. Their content stays published on purpose — see the
     * is_active migration — so this gates access, not the library.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isTeacher(): bool
    {
        return $this->role === self::ROLE_TEACHER;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    // Teacher-owned content.

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'teacher_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class, 'teacher_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'teacher_id');
    }

    /** YouTube channels this teacher has verified ownership of via OAuth. */
    public function youtubeChannels(): HasMany
    {
        return $this->hasMany(YoutubeChannel::class, 'teacher_id');
    }

    // Student activity.

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'student_id');
    }

    public function lessonViews(): HasMany
    {
        return $this->hasMany(LessonView::class, 'student_id');
    }

    public function favourites(): HasMany
    {
        return $this->hasMany(Favourite::class, 'student_id');
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class, 'student_id');
    }

    /**
     * Initials for the avatar fallback. "Nur Aisyah Rahim" becomes "NA".
     */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $letters = array_slice(array_map(fn ($p) => mb_substr($p, 0, 1), $parts), 0, 2);

        return mb_strtoupper(implode('', $letters)) ?: mb_strtoupper(mb_substr((string) $this->username, 0, 2));
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar ? Storage::disk('uploads')->url($this->avatar) : null;
    }

    /**
     * Where this user lands after logging in.
     */
    public function homeRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => route('admin.dashboard'),
            self::ROLE_TEACHER => route('cikgu.dashboard'),
            default => route('belajar.index'),
        };
    }
}
