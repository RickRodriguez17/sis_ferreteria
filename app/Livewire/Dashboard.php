<?php

namespace App\Livewire;

use App\Services\DashboardService;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(DashboardService $service)
    {
        return view('livewire.dashboard', $service->summary())->layout('layouts.app');
    }
}
