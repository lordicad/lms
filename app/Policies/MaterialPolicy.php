<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, Material $material): bool
    {
        return $user->isTeacher() && $user->id === $material->teacher_id;
    }

    public function delete(User $user, Material $material): bool
    {
        return $this->update($user, $material);
    }

    /**
     * Teachers and admins browse and audit the whole library. A student only gets what they could
     * have reached from a chapter page (Material::visibleToStudents): material ids are sequential,
     * so "everyone may download" let a student walk /muat-turun/bahan/1..N and pull files from
     * retired chapters and from lessons still in draft.
     */
    public function download(User $user, Material $material): bool
    {
        return ! $user->isStudent() || $material->isVisibleToStudents();
    }
}
