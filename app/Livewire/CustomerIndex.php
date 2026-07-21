<?php

namespace App\Livewire;

use App\Domain\Enums\CustomerType;
use App\Livewire\Traits\WithTableState;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class CustomerIndex extends Component
{
    use WithTableState;

    public string $type = '';

    public string $credit = '';

    public string $status = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Customer::class);
    }

    public function create(): void
    {
        Gate::authorize('create', Customer::class);
        $this->dispatch('customer-form-open');
    }

    public function edit(int $id): void
    {
        Gate::authorize('update', Customer::findOrFail($id));
        $this->dispatch('customer-form-open', id: $id);
    }

    public function toggle(int $id, CustomerService $service): void
    {
        $customer = Customer::findOrFail($id);
        Gate::authorize('update', $customer);
        $service->toggle($customer);
        session()->flash('success', 'Estado del cliente actualizado.');
    }

    public function delete(int $id, CustomerService $service): void
    {
        $customer = Customer::findOrFail($id);
        Gate::authorize('delete', $customer);
        $service->delete($customer);
        session()->flash('success', 'Cliente eliminado correctamente.');
    }

    public function render()
    {
        $customers = Customer::query()
            ->withCount(['sales', 'quotations', 'credits'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('document_number', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->type !== '', fn ($query) => $query->where('type', $this->type))
            ->when($this->credit === 'yes', fn ($query) => $query->where('credit_limit', '>', 0))
            ->when($this->credit === 'no', fn ($query) => $query->where(fn ($query) => $query->whereNull('credit_limit')->orWhere('credit_limit', '<=', 0)))
            ->when($this->status !== '', fn ($query) => $query->where('is_active', $this->status === 'active'))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.customer-index', [
            'customers' => $customers,
            'types' => CustomerType::cases(),
        ])->layout('layouts.app');
    }
}
