<?php

namespace App\Policies;

use App\Models\User;

abstract class PermissionPolicy
{
    protected string $resource;

    public function viewAny(User $user): bool
    {
        return $user->can("{$this->resource}.view");
    }

    public function view(User $user, object $model): bool
    {
        return $user->can("{$this->resource}.view");
    }

    public function create(User $user): bool
    {
        return $user->can("{$this->resource}.create");
    }

    public function update(User $user, object $model): bool
    {
        return $user->can("{$this->resource}.update");
    }

    public function delete(User $user, object $model): bool
    {
        return $user->can("{$this->resource}.delete");
    }
}
