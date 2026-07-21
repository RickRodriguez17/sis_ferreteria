<?php

namespace App\Livewire;

use App\Exceptions\CreditLimitExceededException;
use App\Exceptions\InsufficientStockException;
use App\Livewire\Traits\WithTableState;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\PaymentAccount;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class SaleForm extends Component
{
    use WithTableState;

    public bool $withInvoice = false;

    public string $productSearch = '';

    public string $customerSearch = '';

    public string $customerId = '';

    public string $locationId = '';

    public string $paymentType = 'cash';

    public string $paymentMethod = 'cash';

    public string $paymentAccountId = '';

    public string $discount = '0';

    public array $items = [];

    public function mount(): void
    {
        Gate::authorize('create', Sale::class);
        $this->locationId = (string) (Location::query()->where('is_default', true)->value('id') ?? Location::query()->active()->value('id') ?? '');
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
        $this->items[] = [
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'quantity' => '1',
            'unit_price' => (string) $price,
            'subtotal' => (string) $price,
        ];
        $this->productSearch = '';
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedWithInvoice(): void
    {
        foreach ($this->items as $index => $item) {
            $presentation = Presentation::find($item['presentation_id']);
            if ($presentation) {
                $this->items[$index]['unit_price'] = (string) ($this->withInvoice ? $presentation->price_with_invoice : $presentation->price_without_invoice);
            }
        }
        $this->recalculate();
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
        $this->recalculate();
    }

    public function updatedDiscount(): void
    {
        $this->discount = max(0, (float) $this->discount).'';
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

    public function save(SaleService $service): void
    {
        $this->validate([
            'withInvoice' => ['boolean'],
            'locationId' => ['required', 'exists:locations,id'],
            'customerId' => ['nullable', 'exists:customers,id'],
            'paymentType' => ['required', 'in:cash,credit,mixed'],
            'paymentMethod' => ['required', 'in:cash,qr,transfer'],
            'paymentAccountId' => ['nullable', 'integer', 'exists:payment_accounts,id'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.presentation_id' => ['required', 'exists:presentations,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $subtotal = collect($this->items)->sum('subtotal');
        if ((float) $this->discount > (float) $subtotal) {
            $this->addError('discount', 'El descuento no puede superar el subtotal.');

            return;
        }
        if (in_array($this->paymentType, ['credit', 'mixed'], true) && $this->customerId === '') {
            $this->addError('customerId', 'Debe seleccionar un cliente para una venta a crédito.');

            return;
        }
        $paymentAccount = $this->paymentMethod !== 'cash' && $this->paymentAccountId !== ''
            ? PaymentAccount::query()->active()->find($this->paymentAccountId)
            : null;
        if ($this->paymentMethod !== 'cash' && ! $paymentAccount) {
            $this->addError('paymentAccountId', 'Debe seleccionar una cuenta activa para este método de pago.');

            return;
        }

        $items = array_map(fn (array $item): array => [
            'product_id' => $item['product_id'],
            'presentation_id' => $item['presentation_id'],
            'quantity' => $item['quantity'],
            'base_quantity' => 0,
            'unit_price' => $item['unit_price'],
            'subtotal' => (float) $item['quantity'] * (float) $item['unit_price'],
        ], $this->items);

        try {
            $sale = $service->register([
                'customer_id' => $this->customerId !== '' ? $this->customerId : null,
                'with_invoice' => $this->withInvoice,
                'payment_type' => $this->paymentType,
                'payment_method' => $this->paymentMethod,
                'payment_account_id' => $paymentAccount?->id,
                'subtotal' => $subtotal,
                'discount' => $this->discount,
                'total' => max(0, (float) $subtotal - (float) $this->discount),
                'location_id' => $this->locationId,
            ], $items);
        } catch (CreditLimitExceededException) {
            $this->addError('customerId', 'La venta supera el límite de crédito disponible del cliente.');

            return;
        } catch (InsufficientStockException) {
            $this->addError('items', 'No hay existencias suficientes para completar la venta.');

            return;
        } catch (Throwable) {
            $this->addError('items', 'No fue posible registrar la venta. Verifique los datos e inténtelo nuevamente.');

            return;
        }

        session()->flash('success', 'Venta registrada correctamente.');
        $this->redirectRoute('sales.show', $sale, navigate: true);
    }

    public function render()
    {
        $products = Product::query()->active()->with('presentations')->when($this->productSearch !== '', fn ($query) => $query->search($this->productSearch))->orderBy('name')->limit(20)->get();
        $customers = Customer::query()->active()->when($this->customerSearch !== '', fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$this->customerSearch.'%')->orWhere('document_number', 'like', '%'.$this->customerSearch.'%')->orWhere('phone', 'like', '%'.$this->customerSearch.'%')))->orderBy('name')->limit(15)->get();
        $locations = Location::query()->active()->orderBy('name')->get();
        $paymentAccounts = PaymentAccount::query()->active()
            ->when($this->paymentMethod === 'qr', fn ($query) => $query->where('type', 'qr'))
            ->when($this->paymentMethod === 'transfer', fn ($query) => $query->whereIn('type', ['transfer', 'bank']))
            ->orderBy('name')->get();
        $productIds = collect($this->items)->pluck('product_id')->filter();
        $stocks = Inventory::query()->whereIn('product_id', $productIds)->where('location_id', $this->locationId ?: 0)->pluck('quantity', 'product_id');
        $lineStocks = collect($this->items)->mapWithKeys(function (array $item, int $index) use ($stocks): array {
            $presentation = Presentation::find($item['presentation_id']);
            $equivalence = $presentation ? (float) $presentation->equivalence : 1;

            return [$index => $equivalence > 0 ? (float) ($stocks[$item['product_id']] ?? 0) / $equivalence : 0];
        });

        return view('livewire.sale-form', compact('products', 'customers', 'locations', 'stocks', 'lineStocks', 'paymentAccounts'))->layout('layouts.app');
    }

    private function recalculate(): void
    {
        foreach ($this->items as $index => $item) {
            $this->items[$index]['subtotal'] = number_format((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0), 2, '.', '');
        }
    }
}
