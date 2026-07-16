<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    /**
     * Any teacher may add lessons. Only the owner may touch an existing one.
     */
    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->isTeacher() && $user->id === $lesson->teacher_id;
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson);
    }

    /**
     * Students only ever reach published lessons; the owning teacher can preview drafts.
     */
    public function view(User $user, Lesson $lesson): bool
    {
        return $lesson->is_published || $user->id === $lesson->teacher_id;
    }
}
