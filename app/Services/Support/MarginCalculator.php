<?php

namespace App\Services\Support;

use App\Models\Product;
use App\Models\Setting;

class MarginCalculator
{
    public function suggested(Product $product, ?float $margin = null): string
    {
        $margin ??= (float) (Setting::where('key', 'default_margin')->value('value') ?? 0.30);

        return bcmul((string) $product->cost, bcadd('1', (string) $margin, 4), 2);
    }
}
