<?php

namespace Database\Factories;

use App\Models\Location;

class LocationFactory extends GenericFactory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->words(2, true), 'code' => fake()->unique()->lexify('LOC-???'), 'is_active' => true, 'is_default' => false];
    }
}
