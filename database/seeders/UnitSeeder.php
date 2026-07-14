<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['name' => 'Unidad', 'abbreviation' => 'und'], ['name' => 'Metro', 'abbreviation' => 'm'], ['name' => 'Kilogramo', 'abbreviation' => 'kg'], ['name' => 'Caja', 'abbreviation' => 'caja'], ['name' => 'Rollo', 'abbreviation' => 'rollo'], ['name' => 'Bolsa', 'abbreviation' => 'bolsa']] as $unit) {
            Unit::updateOrCreate(['abbreviation' => $unit['abbreviation']], ['name' => $unit['name'], 'is_active' => true]);
        }
    }
}
