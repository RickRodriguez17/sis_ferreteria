<?php

namespace Database\Factories;

use App\Models\Category;

class CategoryFactory extends GenericFactory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->words(2, true), 'slug' => fake()->unique()->slug(), 'is_active' => true];
    }
}
