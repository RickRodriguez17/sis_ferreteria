<?php

namespace Database\Factories;

use App\Models\Unit;

class UnitFactory extends GenericFactory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->word(), 'abbreviation' => fake()->unique()->lexify('u??'), 'is_active' => true];
    }
}
