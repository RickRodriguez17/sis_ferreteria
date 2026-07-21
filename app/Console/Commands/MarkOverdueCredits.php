<?php

namespace App\Console\Commands;

use App\Services\CreditService;
use Illuminate\Console\Command;

class MarkOverdueCredits extends Command
{
    protected $signature = 'credits:mark-overdue';

    protected $description = 'Marca como vencidos los créditos con saldo pendiente.';

    public function handle(CreditService $service): int
    {
        $count = $service->markOverdue();
        $this->info("Se marcaron {$count} crédito(s) como vencido(s).");

        return self::SUCCESS;
    }
}
