<?php

namespace App\Policies;

class PaymentAccountPolicy extends PermissionPolicy
{
    protected string $resource = 'payment_accounts';
}
