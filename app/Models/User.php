<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'school_id',
        'school_class_id',
        'phone',
        'position',
        'guardian_name',
        'guardian_phone',
        'guardian_email',
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
            'school_id' => 'integer',
            'school_class_id' => 'integer',
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'password_changed_at' => 'datetime',
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

    /**
     * Whether this account signs in with its email address.
     *
     * Email is the sign-in identifier for everyone: the admin sets it, it is unique, and the owner
     * cannot change it, so it stays stable. `username` is a display nickname the owner is free to
     * change, which is exactly why it cannot be the thing they sign in with.
     *
     * The fallback covers accounts predating that rule, which have no email and would otherwise be
     * locked out; they keep signing in with their username.
     */
    public function signsInWithEmail(): bool
    {
        return filled($this->email);
    }

    /** The exact value to type into the sign-in field. */
    public function signInIdentifier(): string
    {
        return $this->signsInWithEmail() ? $this->email : $this->username;
    }

    /**
     * Teacher and student accounts are created by an admin, who picks the first password and hands
     * it over — so until the owner replaces it, someone else knows it. A null `password_changed_at`
     * means exactly that, and sends them to the change screen before they can use the system.
     * Admins set up their own accounts, so they are not asked.
     */
    public function mustChangePassword(): bool
    {
        return $this->password_changed_at === null && ! $this->isAdmin();
    }

    /** Record that the owner has chosen this password themselves. */
    public function markPasswordChanged(): void
    {
        $this->forceFill(['password_changed_at' => now()])->save();
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    // School / class / homeroom.

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** The student's class. */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    /**
     * The class this teacher is homeroom teacher of, if any. This is the writable side of the
     * homeroom relationship — homeroom_teacher_id lives on school_classes, so a teacher owns at
     * most one homeroom class (enforced by a unique index).
     */
    public function homeroomClass(): HasOne
    {
        return $this->hasOne(SchoolClass::class, 'homeroom_teacher_id');
    }

    /** Subjects this teacher teaches (many-to-many). */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_teacher', 'user_id', 'subject_id');
    }

    /**
     * A student's homeroom teacher, derived read-only from their class. Returns null when the
     * student has no class or the class has no homeroom teacher assigned.
     */
    public function homeroomTeacher(): ?User
    {
        return $this->schoolClass?->homeroomTeacher;
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

    /** Activity notifications for this teacher (quiz taken / video favourited / material downloaded). */
    public function teacherNotifications(): HasMany
    {
        return $this->hasMany(TeacherNotification::class, 'teacher_id');
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
