<?php

namespace App\Domain\Enums;

enum CustomerType: string
{
    case Registered = 'registered';
    case Occasional = 'occasional';
}
