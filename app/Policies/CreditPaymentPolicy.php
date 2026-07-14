<?php

namespace App\Policies;

class CreditPaymentPolicy extends PermissionPolicy
{
    protected string $resource = 'payments';
}
