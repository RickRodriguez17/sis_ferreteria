<?php

namespace App\Policies;

class QuotationPolicy extends PermissionPolicy
{
    protected string $resource = 'quotations';
}
