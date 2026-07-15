<?php

namespace App\Livewire;

use App\Domain\Enums\PriceField;
use App\Exceptions\PriceChangeNotAllowedException;
use App\Exceptions\PurchaseCannotBeCancelledException;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Setting;
use App\Services\PriceService;
use App\Services\PurchaseService;
use App\Services\Support\MarginCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class PurchaseShow extends Component
{
    public Purchase $purchase;

    public string $margin = '0.30';

    public array $productMargins = [];

    public string $priceReason = 'Precio sugerido posterior a recepción';

    public function mount(Purchase $purchase): void
    {
        Gate::authorize('view', $purchase);
        $this->margin = (string) (Setting::query()->where('key', 'default_margin')->value('value') ?? '0.30');
        $this->purchase = $purchase->load(['supplier', 'items.product.presentations', 'receptions.location', 'receptions.attachments', 'costHistories.product']);
        foreach ($this->receivedProducts() as $product) {
            $this->productMargins[$product->id] = $this->margin;
        }
    }

    public function cancel(PurchaseService $service): void
    {
        Gate::authorize('update', $this->purchase);
        try {
            $this->purchase = $service->cancel($this->purchase);
            session()->flash('success', 'Compra cancelada.');
        } catch (PurchaseCannotBeCancelledException $exception) {
            $this->addError('cancel', $exception->getMessage());
        }
    }

    public function updatedMargin(): void
    {
        $this->validateOnly('margin', ['margin' => ['required', 'numeric', 'min:0', 'max:10']]);
        foreach ($this->receivedProducts() as $product) {
            $this->productMargins[$product->id] = $this->margin;
        }
    }

    public function updatedProductMargins(int|string $productId): void
    {
        $this->validateOnly("productMargins.{$productId}", ["productMargins.{$productId}" => ['required', 'numeric', 'min:0', 'max:10']]);
    }

    public function canApplyPrices(): bool
    {
        return (bool) auth()->user()?->can('prices.update');
    }

    public function suggestedPrice(Product $product, int|string|null $productId = null): string
    {
        $id = (int) ($productId ?? $product->id);
        $margin = (float) ($this->productMargins[$id] ?? $this->margin);

        return app(MarginCalculator::class)->suggested($product, $margin);
    }

    public function applySuggestedPrice(int $presentationId, string $field, int $productId, PriceService $priceService): void
    {
        abort_unless($this->canApplyPrices(), 403);
        $this->validate([
            'priceReason' => ['required', 'string', 'max:255'],
            'margin' => ['required', 'numeric', 'min:0', 'max:10'],
            "productMargins.{$productId}" => ['required', 'numeric', 'min:0', 'max:10'],
        ]);
        $priceField = PriceField::tryFrom($field);
        abort_if(! in_array($priceField, [PriceField::PriceWithInvoice, PriceField::PriceWithoutInvoice], true), 422);
        $product = $this->receivedProducts()->firstWhere('id', $productId);
        abort_unless($product !== null, 404);
        $presentation = $product->presentations->firstWhere('id', $presentationId);
        abort_unless($presentation !== null, 404);

        try {
            $priceService->changePrice($presentation, $priceField, $this->suggestedPrice($product, $productId), $this->priceReason);
            $this->purchase = $this->purchase->fresh(['supplier', 'items.product.presentations', 'receptions.location', 'receptions.attachments', 'costHistories.product']);
            session()->flash('success', 'Precio actualizado y registrado en el historial.');
            $this->resetErrorBag();
        } catch (PriceChangeNotAllowedException|\InvalidArgumentException $exception) {
            $this->addError('price', $exception->getMessage());
        }
    }

    /** @return Collection<int, Product> */
    public function receivedProducts()
    {
        return $this->purchase->items
            ->filter(fn ($item): bool => (float) $item->quantity_received > 0)
            ->map(fn ($item) => $item->product)
            ->unique('id')
            ->values();
    }

    public function render()
    {
        return view('livewire.purchase-show')->layout('layouts.app');
    }
}
