<?php

namespace App\Policies;

use App\Enums\UserRoleEnum;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Administrators and managers may create tasks for any project.
     * Other members may create tasks only for projects they belong to.
     */
    public function create(User $user, Project $project): bool
    {
        if ($this->hasManagerPrivileges($user)) {
            return true;
        }

        return $project->users()
            ->where('id', $user->id)
            ->exists();
    }

    /**
     * Administrators, managers, and the assigned user may update a task.
     */
    public function update(User $user, Task $task): bool
    {
        return $this->hasManagerPrivileges($user) || $task->assigned_to === $user->id;
    }

    /**
     * Administrators, managers, and the assigned user may delete a task.
     */
    public function delete(User $user, Task $task): bool
    {
        return $this->hasManagerPrivileges($user) || $task->assigned_to === $user->id;
    }

    private function hasManagerPrivileges(User $user): bool
    {
        $role = $user->role instanceof UserRoleEnum ? $user->role->value : $user->role;

        return in_array($role, [
            UserRoleEnum::ADMIN->value,
            UserRoleEnum::MANAGER->value,
        ], true);
    }
}

