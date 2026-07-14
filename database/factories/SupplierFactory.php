<?php

namespace Database\Factories;

use App\Models\Supplier;

class SupplierFactory extends GenericFactory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return ['name' => fake()->company(), 'document_number' => fake()->unique()->numerify('SUP-#####'), 'phone' => fake()->phoneNumber(), 'email' => fake()->safeEmail(), 'address' => fake()->address(), 'is_active' => true];
    }
}
