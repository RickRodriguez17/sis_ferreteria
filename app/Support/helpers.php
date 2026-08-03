<?php

use App\Support\CompanySettings;

if (! function_exists('money')) {
    function money(float|int|string|null $amount): string
    {
        return app(CompanySettings::class)->money($amount);
    }
}

if (! function_exists('qty')) {
    function qty(float|int|string|null $value): string
    {
        $s = number_format((float) $value, 4, '.', '');
        $s = rtrim(rtrim($s, '0'), '.');

        return $s === '' || $s === '-0' ? '0' : $s;
    }
}
