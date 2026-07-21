<?php

namespace App\Livewire;

use App\Domain\Enums\QuotationStatus;
use App\Models\Customer;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\QuotationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class QuotationForm extends Component
{
    public ?Quotation $quotation = null;

    public bool $withInvoice = false;

    public string $validUntil = '';

    public string $productSearch = '';

    public string $customerSearch = '';

    public string $customerId = '';

    public array $items = [];

    public function mount(?Quotation $quotation = null): void
    {
        $this->quotation = $quotation?->exists ? $quotation->load('items') : null;
        Gate::authorize($this->quotation ? 'update' : 'create', $this->quotation ?: Quotation::class);
        if ($this->quotation) {
            abort_unless($this->quotation->status === QuotationStatus::Open, 403);
            $this->withInvoice = $this->quotation->with_invoice;
            $this->validUntil = $this->quotation->valid_until ? Carbon::parse((string) $this->quotation->valid_until)->format('Y-m-d') : '';
            $this->customerId = (string) ($this->quotation->customer_id ?? '');
            $this->items = $this->quotation->items->map(fn ($item): array => ['product_id' => $item->product_id, 'presentation_id' => $item->presentation_id, 'quantity' => (string) $item->quantity, 'unit_price' => (string) $item->unit_price, 'subtotal' => (string) $item->subtotal])->all();
        }
    }

    public function selectProduct(int $productId): void
    {
        $product = Product::with('presentations')->findOrFail($productId);
        $presentation = $product->presentations->firstWhere('is_active', true) ?? $product->presentations->first();
        if (! $presentation) {
            $this->addError('productSearch', 'El producto no tiene presentaciones activas.');

            return;
        }
        $price = $this->withInvoice ? $presentation->price_with_invoice : $presentation->price_without_invoice;
        $this->items[] = ['product_id' => $product->id, 'presentation_id' => $presentation->id, 'quantity' => '1', 'unit_price' => (string) $price, 'subtotal' => (string) $price];
        $this->productSearch = '';
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedItems(mixed $value = null, ?string $key = null): void
    {
        if ($key !== null && str_ends_with($key, '.presentation_id')) {
            [$index] = explode('.', $key);
            $presentation = Presentation::find($value);
            if ($presentation) {
                $this->items[(int) $index]['unit_price'] = (string) ($this->withInvoice ? $presentation->price_with_invoice : $presentation->price_without_invoice);
            }
        }
        foreach ($this->items as $index => $item) {
            $this->items[$index]['subtotal'] = number_format((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0), 2, '.', '');
        }
    }

    public function updatedWithInvoice(): void
    {
        foreach ($this->items as $index => $item) {
            $presentation = Presentation::find($item['presentation_id']);
            if ($presentation) {
                $this->items[$index]['unit_price'] = (string) ($this->withInvoice ? $presentation->price_with_invoice : $presentation->price_without_invoice);
            }
        }
        $this->updatedItems();
    }

    public function selectCustomer(int $id): void
    {
        $this->customerId = (string) $id;
        $this->customerSearch = '';
    }

    #[On('customer-created')]
    public function customerCreated(int $customerId): void
    {
        $this->selectCustomer($customerId);
    }

    public function save(QuotationService $service): void
    {
        $this->validate([
            'withInvoice' => ['boolean'],
            'validUntil' => ['nullable', 'date'],
            'customerId' => ['nullable', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.presentation_id' => ['required', 'exists:presentations,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);
        $items = array_map(fn (array $item): array => ['product_id' => $item['product_id'], 'presentation_id' => $item['presentation_id'], 'quantity' => $item['quantity'], 'unit_price' => $item['unit_price'], 'subtotal' => (float) $item['quantity'] * (float) $item['unit_price']], $this->items);
        $data = ['customer_id' => $this->customerId !== '' ? $this->customerId : null, 'with_invoice' => $this->withInvoice, 'valid_until' => $this->validUntil ?: null, 'items' => $items];
        $saved = $this->quotation ? $service->update($this->quotation, $data) : $service->create($data);
        session()->flash('success', 'Cotización guardada correctamente.');
        $this->redirectRoute('quotations.show', $saved, navigate: true);
    }

    public function render()
    {
        $products = Product::query()->active()->with('presentations')->when($this->productSearch !== '', fn ($q) => $q->search($this->productSearch))->orderBy('name')->limit(20)->get();
        $customers = Customer::query()->active()->when($this->customerSearch !== '', fn ($q) => $q->where('name', 'like', '%'.$this->customerSearch.'%')->orWhere('document_number', 'like', '%'.$this->customerSearch.'%'))->orderBy('name')->limit(15)->get();

        return view('livewire.quotation-form', compact('products', 'customers'))->layout('layouts.app');
    }
}
