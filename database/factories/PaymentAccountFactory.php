<?php

namespace Database\Factories;

use App\Models\PaymentAccount;

class PaymentAccountFactory extends GenericFactory
{
    protected $model = PaymentAccount::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' Digital',
            'type' => 'qr',
            'details' => fake()->numerify('##########'),
            'is_active' => true,
        ];
    }
}
