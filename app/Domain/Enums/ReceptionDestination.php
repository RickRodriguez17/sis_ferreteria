<?php

namespace App\Domain\Enums;

enum ReceptionDestination: string
{
    case Store = 'tienda';
    case Work = 'obra';
}
