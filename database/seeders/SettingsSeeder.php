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
            'company_legal_name' => ['value' => 'Construir a tu Alcance', 'type' => 'string'],
            'company_tax_id' => ['value' => '', 'type' => 'string'],
            'company_address' => ['value' => 'Av. Principal', 'type' => 'string'],
            'company_phone' => ['value' => '', 'type' => 'string'],
            'company_email' => ['value' => '', 'type' => 'string'],
            'company_logo_path' => ['value' => '', 'type' => 'string'],
            'sale_footer' => ['value' => 'Gracias por su compra.', 'type' => 'string'],
            'quotation_footer' => ['value' => 'Precios sujetos a disponibilidad.', 'type' => 'string'],
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
