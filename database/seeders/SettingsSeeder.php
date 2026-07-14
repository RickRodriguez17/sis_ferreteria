<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'currency' => ['value' => 'BOB', 'type' => 'string'],
            'tax_rate' => ['value' => '0', 'type' => 'decimal'],
            'default_margin' => ['value' => '0.30', 'type' => 'decimal'],
            'company_name' => ['value' => 'Construir a tu Alcance', 'type' => 'string'],
            'company_address' => ['value' => 'Av. Principal', 'type' => 'string'],
            'default_credit_days' => ['value' => '30', 'type' => 'integer'],
            'series.purchase' => ['value' => 'COM-0000', 'type' => 'document_series'],
            'series.quotation' => ['value' => 'COT-0000', 'type' => 'document_series'],
            'series.sale' => ['value' => 'VEN-0000', 'type' => 'document_series'],
            'series.reception' => ['value' => 'REC-0000', 'type' => 'document_series'],
        ];
        foreach ($settings as $key => $data) {
            Setting::updateOrCreate(['key' => $key], $data);
        }
    }
}
