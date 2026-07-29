<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function view(User $user, object $model): bool
    {
        return $user->can('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user, object $model): bool
    {
        return $user->can('users.manage');
    }

    public function delete(User $user, object $model): bool
    {
        return $user->can('users.manage');
    }
}
