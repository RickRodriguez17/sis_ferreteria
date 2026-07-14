<?php

namespace Database\Factories;

use App\Models\Customer;

class CustomerFactory extends GenericFactory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return ['type' => 'registered', 'name' => fake()->name(), 'document_number' => fake()->unique()->numerify('C-#####'), 'phone' => fake()->phoneNumber(), 'email' => fake()->safeEmail(), 'address' => fake()->address(), 'credit_limit' => 1000, 'is_active' => true];
    }
}
