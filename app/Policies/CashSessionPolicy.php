<?php

namespace App\Policies;

use App\Models\User;

class CashSessionPolicy extends PermissionPolicy
{
    protected string $resource = 'cash';

    public function open(User $user): bool
    {
        return $user->can('cash.open');
    }

    public function close(User $user, object $model): bool
    {
        return $user->can('cash.close');
    }
}
