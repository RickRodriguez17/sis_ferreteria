<?php

namespace App\Livewire;

use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Throwable;

class SaleShow extends Component
{
    public Sale $sale;

    public array $pendingPrices = [];

    public bool $showPendingPrices = false;

    public function mount(Sale $sale): void
    {
        Gate::authorize('view', $sale);
        $this->sale = $sale->load(['customer', 'location', 'items.product', 'items.presentation', 'credit.payments']);
    }

    public function definePendingPrices(SaleService $service): void
    {
        Gate::authorize('prices.update');
        try {
            $this->sale = $service->definePendingPrices($this->sale, $this->pendingPrices);
            $this->showPendingPrices = false;
            $this->dispatch('toast', message: 'Precios pendientes definidos correctamente.', type: 'success');
        } catch (Throwable $exception) {
            $this->addError('pendingPrices', $exception->getMessage());
            $this->dispatch('alert', type: 'error', title: 'No fue posible definir los precios', message: $exception->getMessage());
        }
    }

    public function openPendingPrices(): void
    {
        Gate::authorize('prices.update');
        $this->pendingPrices = $this->sale->items->where('price_pending', true)->mapWithKeys(fn ($item): array => [$item->id => ''])->all();
        $this->showPendingPrices = true;
    }

    public function render()
    {
        return view('livewire.sale-show')->layout('layouts.app');
    }
}
