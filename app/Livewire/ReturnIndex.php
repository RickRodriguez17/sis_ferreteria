<?php

namespace App\Livewire;

use App\Livewire\Traits\WithTableState;
use App\Models\CustomerReturn;
use App\Models\SupplierReturn;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ReturnIndex extends Component
{
    use WithTableState;

    public string $type = '';

    public string $from = '';

    public string $to = '';

    public function mount(?string $type = null): void
    {
        Gate::authorize('viewAny', CustomerReturn::class);
        $this->type = in_array($type, ['customer', 'supplier'], true) ? $type : '';
    }

    public function updated($property): void
    {
        if (in_array($property, ['type', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        if ($this->type === 'supplier') {
            $returns = SupplierReturn::query()
                ->with(['supplier:id,name', 'reception:id,code'])
                ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query->where('code', 'like', '%'.$this->search.'%')->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', '%'.$this->search.'%'))->orWhereHas('reception', fn ($reception) => $reception->where('code', 'like', '%'.$this->search.'%'))))
                ->when($this->from !== '', fn ($query) => $query->whereDate('returned_at', '>=', $this->from))
                ->when($this->to !== '', fn ($query) => $query->whereDate('returned_at', '<=', $this->to))
                ->latest('returned_at')
                ->paginate($this->perPage);
        } else {
            $returns = CustomerReturn::query()
                ->with(['customer:id,name', 'sale:id,code'])
                ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query->where('code', 'like', '%'.$this->search.'%')->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', '%'.$this->search.'%'))->orWhereHas('sale', fn ($sale) => $sale->where('code', 'like', '%'.$this->search.'%'))))
                ->when($this->from !== '', fn ($query) => $query->whereDate('returned_at', '>=', $this->from))
                ->when($this->to !== '', fn ($query) => $query->whereDate('returned_at', '<=', $this->to))
                ->latest('returned_at')
                ->paginate($this->perPage);
        }

        return view('livewire.return-index', compact('returns'))->layout('layouts.app');
    }
}
