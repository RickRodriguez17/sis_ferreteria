<?php

namespace App\Livewire;

use App\Domain\Enums\PurchaseStatus;
use App\Livewire\Traits\WithTableState;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class PurchaseIndex extends Component
{
    use WithTableState;

    public string $status = '';

    public string $supplierId = '';

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Purchase::class);
        $this->supplierId = (string) request()->query('supplier_id', '');
    }

    public function render()
    {
        $purchases = Purchase::query()
            ->with('supplier:id,name')
            ->withCount(['items', 'receptions'])
            ->when($this->search !== '', fn ($query) => $query->where('code', 'like', '%'.$this->search.'%'))
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->supplierId !== '', fn ($query) => $query->where('supplier_id', $this->supplierId))
            ->when($this->from !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->to))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.purchase-index', ['purchases' => $purchases, 'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']), 'statuses' => PurchaseStatus::cases()])->layout('layouts.app');
    }
}
