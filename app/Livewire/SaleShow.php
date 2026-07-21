<?php

namespace App\Livewire;

use App\Models\Sale;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class SaleShow extends Component
{
    public Sale $sale;

    public function mount(Sale $sale): void
    {
        Gate::authorize('view', $sale);
        $this->sale = $sale->load(['customer', 'location', 'items.product', 'items.presentation', 'credit.payments']);
    }

    public function render()
    {
        return view('livewire.sale-show')->layout('layouts.app');
    }
}
