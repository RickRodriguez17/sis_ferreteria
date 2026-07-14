<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Patio' => true, 'Muestrario' => false, 'Depósito' => false] as $name => $default) {
            Location::updateOrCreate(['name' => $name], ['is_active' => true, 'is_default' => $default]);
        }
    }
}
