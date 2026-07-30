<?php

namespace App\Livewire;

use App\Services\CreditAlertService;
use App\Services\DashboardService;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(DashboardService $service, CreditAlertService $creditAlerts)
    {
        return view('livewire.dashboard', $service->summary($creditAlerts))->layout('layouts.app');
    }
}
