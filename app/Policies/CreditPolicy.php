<?php

namespace App\Policies;

class CreditPolicy extends PermissionPolicy
{
    protected string $resource = 'credits';
}
