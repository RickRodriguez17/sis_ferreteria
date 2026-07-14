<?php

namespace App\Events;

use App\Models\Inventory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockBelowMinimum
{
    use Dispatchable, SerializesModels;

    public function __construct(public Inventory $inventory) {}
}
