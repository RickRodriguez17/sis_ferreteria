<?php

namespace Database\Factories;

use App\Models\Brand;

class BrandFactory extends GenericFactory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->company(), 'is_active' => true];
    }
}
