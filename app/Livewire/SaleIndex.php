<?php

namespace App\Livewire;

use App\Domain\Enums\SaleStatus;
use App\Livewire\Traits\WithTableState;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Throwable;

class SaleIndex extends Component
{
    use WithTableState;

    public string $status = '';

    public string $customerId = '';

    public string $from = '';

    public string $to = '';

    public string $withInvoice = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Sale::class);
    }

    public function cancel(int $id, SaleService $service): void
    {
        $sale = Sale::findOrFail($id);
        Gate::authorize('delete', $sale);
        try {
            $service->cancel($sale);
            $this->dispatch('toast', message: 'Venta cancelada correctamente.', type: 'success');
        } catch (Throwable) {
            $this->addError('cancel', 'No fue posible cancelar la venta.');
        }
    }

    public function render()
    {
        $sales = Sale::query()->with('customer:id,name')->withCount(['items', 'items as pending_price_items_count' => fn ($query) => $query->where('price_pending', true)])->when($this->search !== '', fn ($q) => $q->where('code', 'like', '%'.$this->search.'%'))->when($this->status !== '', fn ($q) => $q->where('status', $this->status))->when($this->customerId !== '', fn ($q) => $q->where('customer_id', $this->customerId))->when($this->from !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->from))->when($this->to !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->to))->when($this->withInvoice !== '', fn ($q) => $q->where('with_invoice', $this->withInvoice === 'yes'))->latest()->paginate($this->perPage);

        return view('livewire.sale-index', ['sales' => $sales, 'customers' => Customer::query()->active()->orderBy('name')->get(['id', 'name']), 'statuses' => SaleStatus::cases()])->layout('layouts.app');
    }
}
