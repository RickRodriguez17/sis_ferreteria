<?php

namespace App\Policies;

class CustomerPolicy extends PermissionPolicy
{
    protected string $resource = 'customers';
}
