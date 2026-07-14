<?php

namespace App\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogBusinessEvent implements ShouldQueue
{
    use Queueable;

    public function handle(object $event): void
    {
        // Business events are intentionally extensible for notifications and integrations.
    }
}
