<?php

namespace App\Policies;

class CategoryPolicy extends PermissionPolicy
{
    protected string $resource = 'categories';
}
