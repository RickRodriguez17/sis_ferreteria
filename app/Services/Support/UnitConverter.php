<?php

namespace App\Services\Support;

class UnitConverter
{
    public function toBase(float|int|string $quantity, float|int|string $equivalence): string
    {
        return bcmul((string) $quantity, (string) $equivalence, 4);
    }

    public function fromBase(float|int|string $baseQuantity, float|int|string $equivalence): string
    {
        return bcdiv((string) $baseQuantity, (string) $equivalence, 4);
    }
}
