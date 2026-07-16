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
     * Everyone signed in may download bahan bantu mengajar.
     */
    public function download(User $user, Material $material): bool
    {
        return true;
    }
}
