<?php

namespace App\Policies;

use App\Models\User;

class PresentationPolicy extends PermissionPolicy
{
    protected string $resource = 'products';

    public function update(User $user, object $model): bool
    {
        return $user->can('prices.update');
    }
}
