<?php

namespace Database\Factories;

use App\Models\CashRegister;

class CashRegisterFactory extends GenericFactory
{
    protected $model = CashRegister::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->words(2, true), 'is_active' => true];
    }
}
