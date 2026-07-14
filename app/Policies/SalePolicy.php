<?php

namespace App\Policies;

class SalePolicy extends PermissionPolicy
{
    protected string $resource = 'sales';
}
