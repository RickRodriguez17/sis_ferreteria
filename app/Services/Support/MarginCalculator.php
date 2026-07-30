<?php

namespace App\Services\Support;

use App\Models\Product;
use App\Support\CompanySettings;

class MarginCalculator
{
    public function suggested(Product $product, ?float $margin = null, ?CompanySettings $settings = null): string
    {
        $margin ??= (float) ($settings ?? app(CompanySettings::class))->defaultMargin();

        return bcmul((string) $product->cost, bcadd('1', (string) $margin, 4), 2);
    }
}
