<?php

namespace App\Events;

use App\Models\Reception;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReceptionPosted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Reception $reception) {}
}
