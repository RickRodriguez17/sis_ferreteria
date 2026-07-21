<?php

namespace App\Livewire;

use App\Domain\Enums\QuotationStatus;
use App\Livewire\Traits\WithTableState;
use App\Models\Customer;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class QuotationIndex extends Component
{
    use WithTableState;

    public string $status = '';

    public string $customerId = '';

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Quotation::class);
    }

    public function duplicate(int $id, QuotationService $service): void
    {
        Gate::authorize('create', Quotation::class);
        $quotation = $service->duplicate(Quotation::findOrFail($id));
        session()->flash('success', 'Cotización duplicada correctamente.');
        $this->redirectRoute('quotations.edit', $quotation, navigate: true);
    }

    public function render()
    {
        $quotations = Quotation::query()->with('customer:id,name')->withCount('items')->when($this->search !== '', fn ($q) => $q->where('code', 'like', '%'.$this->search.'%'))->when($this->status !== '', fn ($q) => $q->where('status', $this->status))->when($this->customerId !== '', fn ($q) => $q->where('customer_id', $this->customerId))->when($this->from !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->from))->when($this->to !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->to))->latest()->paginate($this->perPage);

        return view('livewire.quotation-index', ['quotations' => $quotations, 'customers' => Customer::query()->active()->orderBy('name')->get(['id', 'name']), 'statuses' => QuotationStatus::cases()])->layout('layouts.app');
    }
}
