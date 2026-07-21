<?php

namespace App\Livewire;

use App\Domain\Enums\QuotationStatus;
use App\Models\Location;
use App\Models\Quotation;
use App\Models\Sale;
use App\Services\QuotationService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Throwable;

class QuotationShow extends Component
{
    public Quotation $quotation;

    public string $locationId = '';

    public function mount(Quotation $quotation): void
    {
        Gate::authorize('view', $quotation);
        $this->quotation = $quotation->load(['customer', 'items.product', 'items.presentation', 'sale']);
        $this->locationId = (string) (Location::query()->where('is_default', true)->value('id') ?? Location::query()->active()->value('id') ?? '');
    }

    public function convert(QuotationService $service): void
    {
        Gate::authorize('create', Sale::class);
        abort_unless($this->quotation->status === QuotationStatus::Open, 403);
        try {
            $sale = $service->convertToSale($this->quotation, ['location_id' => $this->locationId]);
            session()->flash('success', 'Cotización convertida en venta correctamente.');
            $this->redirectRoute('sales.show', $sale, navigate: true);
        } catch (Throwable) {
            $this->addError('convert', 'No fue posible convertir la cotización. Verifique existencias y datos de la venta.');
        }
    }

    public function render()
    {
        return view('livewire.quotation-show', ['locations' => Location::query()->active()->orderBy('name')->get()])->layout('layouts.app');
    }
}
