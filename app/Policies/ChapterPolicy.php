<?php

namespace App\Policies;

use App\Models\Chapter;
use App\Models\User;

class ChapterPolicy
{
    /**
     * Chapters are shared taxonomy: any teacher may add or rename one so the list can be
     * matched to the real KSSR syllabus. Nobody may delete a Bab that still holds content.
     */
    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, Chapter $chapter): bool
    {
        return $user->isTeacher();
    }

    public function delete(User $user, Chapter $chapter): bool
    {
        return $user->isTeacher() && $chapter->isEmpty();
    }
}
