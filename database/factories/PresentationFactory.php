<?php

namespace Database\Factories;

use App\Models\Presentation;

class PresentationFactory extends GenericFactory
{
    protected $model = Presentation::class;

    public function definition(): array
    {
        return ['name' => fake()->randomElement(['Unidad', 'Caja', 'Rollo']), 'equivalence' => 1, 'price_without_invoice' => 15, 'price_with_invoice' => 17, 'is_active' => true, 'sort_order' => 0];
    }
}
