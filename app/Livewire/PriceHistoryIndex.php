<?php

namespace App\Livewire;

use App\Models\Presentation;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Services\PriceService;
use Livewire\Component;

class PriceHistoryIndex extends Component
{
    public ?int $productId = null;

    public ?int $presentationId = null;

    public string $field = '';

    public string $newValue = '';

    public string $reason = '';

    public function changePrice(PriceService $service): void
    {
        abort_unless(auth()->user()?->can('prices.update'), 403);
        $this->validate(['presentationId' => ['required', 'exists:presentations,id'], 'field' => ['required', 'in:price_without_invoice,price_with_invoice'], 'newValue' => ['required', 'numeric', 'min:0'], 'reason' => ['required', 'string', 'max:255']]);
        $service->changePrice(Presentation::findOrFail($this->presentationId), $this->field, $this->newValue, $this->reason);
        $this->reset(['presentationId', 'field', 'newValue', 'reason']);
        session()->flash('success', 'Precio actualizado y registrado en historial.');
    }

    public function render()
    {
        return view('livewire.price-history-index', ['history' => PriceHistory::query()->with(['priceable.product:id,name', 'changer:id,name'])->latest()->paginate(15), 'products' => Product::query()->with('presentations:id,product_id,name,price_without_invoice,price_with_invoice')->active()->orderBy('name')->get(['id', 'name'])])->layout('layouts.app');
    }
}
