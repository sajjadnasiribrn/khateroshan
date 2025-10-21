<?php

namespace App\Policies;

use App\Enums\UserRoleEnum;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasSystemAccess($user);
    }

    public function create(User $user): bool
    {
        return $this->hasManagerPrivileges($user);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->hasManagerPrivileges($user) || $project->created_by === $user->id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->hasManagerPrivileges($user) || $project->created_by === $user->id;
    }

    private function hasManagerPrivileges(User $user): bool
    {
        $role = $user->role instanceof UserRoleEnum ? $user->role->value : $user->role;

        return in_array($role, [
            UserRoleEnum::ADMIN->value,
            UserRoleEnum::MANAGER->value,
        ], true);
    }

    private function hasSystemAccess(User $user): bool
    {
        $role = $user->role instanceof UserRoleEnum ? $user->role->value : $user->role;

        return in_array($role, [
            UserRoleEnum::ADMIN->value,
            UserRoleEnum::MANAGER->value,
            UserRoleEnum::MEMBER->value,
        ], true);
    }
}

