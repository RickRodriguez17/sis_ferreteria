<?php

namespace App\Events;

use App\Models\PriceHistory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PriceChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(public PriceHistory $history) {}
}
