<?php

namespace App\Policies;

use App\Models\Credit;
use App\Models\User;

class CreditPolicy extends PermissionPolicy
{
    protected string $resource = 'credits';

    public function cancel(User $user, Credit $credit): bool
    {
        return $user->can('credits.cancel');
    }
}
