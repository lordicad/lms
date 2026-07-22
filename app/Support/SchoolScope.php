<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * One admin oversees one school, so every admin screen is limited to the accounts and content
 * belonging to it. This is the single place that rule is expressed — the controllers only say which
 * of these to apply, so there is one thing to read when checking a school cannot see another's data.
 *
 * It fails closed. An admin with no school gets nothing rather than everything: a forgotten
 * assignment then shows an empty page, which is obvious and harmless, instead of quietly handing
 * one school's records to another's administrator.
 */
class SchoolScope
{
    /** The school the signed-in admin oversees, or null when none is set. */
    public static function currentSchoolId(): ?int
    {
        $user = auth()->user();

        return $user?->isAdmin() ? $user->school_id : null;
    }

    /**
     * Narrow a query over `users` to the admin's school.
     *
     * @param  Builder|QueryBuilder  $query
     */
    public static function users($query, string $table = 'users')
    {
        $schoolId = self::currentSchoolId();

        return $schoolId === null
            ? self::nothing($query)
            : $query->where($table.'.school_id', $schoolId);
    }

    /**
     * Narrow a query over content — lessons, materials, quizzes — to the work of the school's own
     * teachers. Content has no school of its own; it belongs to whoever posted it.
     */
    public static function content(Builder $query, string $relation = 'teacher'): Builder
    {
        $schoolId = self::currentSchoolId();

        return $schoolId === null
            ? self::nothing($query)
            : $query->whereHas($relation, fn (Builder $t) => $t->where('school_id', $schoolId));
    }

    /**
     * The ids of the school's teachers, for the raw queries that cannot use a relation.
     * An empty array when no school is set, which matches nothing.
     *
     * @return array<int, int>
     */
    public static function teacherIds(): array
    {
        $schoolId = self::currentSchoolId();

        return $schoolId === null
            ? []
            : User::where('role', User::ROLE_TEACHER)->where('school_id', $schoolId)->pluck('id')->all();
    }

    /**
     * The ids of the school's students, same purpose.
     *
     * @return array<int, int>
     */
    public static function studentIds(): array
    {
        $schoolId = self::currentSchoolId();

        return $schoolId === null
            ? []
            : User::where('role', User::ROLE_STUDENT)->where('school_id', $schoolId)->pluck('id')->all();
    }

    /** Whether a record belongs to the admin's school — for guarding a route-bound model. */
    public static function allows(?int $schoolId): bool
    {
        $current = self::currentSchoolId();

        return $current !== null && $current === $schoolId;
    }

    /**
     * A query that can never match, used when no school is set.
     *
     * @param  Builder|QueryBuilder  $query
     */
    private static function nothing($query)
    {
        return $query->whereRaw('1 = 0');
    }
}
