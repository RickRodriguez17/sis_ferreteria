<?php

namespace App\Services\Support;

use App\Models\Setting;
use Illuminate\Support\Str;

class CodeGenerator
{
    public function product(): string
    {
        return 'PRD-'.strtoupper(Str::random(8));
    }

    public function document(string $type): string
    {
        $key = "series.{$type}";
        $setting = Setting::firstOrCreate(['key' => $key], ['value' => strtoupper(substr($type, 0, 3)).'-0000', 'type' => 'document_series']);
        [$prefix, $number] = array_pad(explode('-', (string) $setting->value, 2), 2, '0');
        $next = (int) $number + 1;
        $setting->update(['value' => $prefix.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT)]);

        return $prefix.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
