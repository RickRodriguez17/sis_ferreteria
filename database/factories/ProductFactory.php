<?php

namespace Database\Factories;

use App\Models\Product;

class ProductFactory extends GenericFactory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return ['uuid' => fake()->unique()->uuid(), 'code' => fake()->unique()->bothify('PRD-####'), 'name' => fake()->words(3, true), 'description' => fake()->sentence(), 'min_stock' => 2, 'cost' => 10, 'is_active' => true];
    }
}
