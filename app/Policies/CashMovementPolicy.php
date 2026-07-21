<?php

namespace App\Policies;

use App\Models\User;

class CashMovementPolicy extends PermissionPolicy
{
    protected string $resource = 'cash';

    public function create(User $user): bool
    {
        return $user->can('cash.movement');
    }
}
