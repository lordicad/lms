<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->isTeacher() && $user->id === $quiz->teacher_id;
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->update($user, $quiz);
    }

    /**
     * Teachers may preview any published quiz; only the owner sees the statistics.
     */
    public function viewStats(User $user, Quiz $quiz): bool
    {
        return $this->update($user, $quiz);
    }

    /**
     * Students take quizzes. Teachers preview them but never generate an attempt.
     */
    public function attempt(User $user, Quiz $quiz): bool
    {
        return $user->isStudent() && $quiz->is_published && $quiz->isInteractive();
    }
}
