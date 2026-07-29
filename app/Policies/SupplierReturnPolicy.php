<?php

namespace App\Policies;

use App\Models\User;

class SupplierReturnPolicy extends PermissionPolicy
{
    protected string $resource = 'returns';

    public function create(User $user): bool
    {
        return $user->can('returns.supplier.create');
    }
}
