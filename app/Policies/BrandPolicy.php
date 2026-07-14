<?php

namespace App\Policies;

class BrandPolicy extends PermissionPolicy
{
    protected string $resource = 'brands';
}
