<?php

namespace App\Policies;

class PurchasePolicy extends PermissionPolicy
{
    protected string $resource = 'purchases';
}
