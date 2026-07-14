<?php

namespace App\Events;

use App\Models\CreditPayment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreditPaymentRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public CreditPayment $payment) {}
}
