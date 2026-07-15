<?php

namespace App\Livewire;

use App\Livewire\Traits\WithTableState;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class SupplierIndex extends Component
{
    use WithTableState;

    public string $status = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $documentType = null;

    public ?string $documentNumber = null;

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $address = null;

    public bool $isActive = true;

    public function create(): void
    {
        Gate::authorize('create', Supplier::class);
        $this->resetForm();
        $this->showModal = true;
        $this->dispatch('open-modal', 'supplier-record');
    }

    public function edit(int $id): void
    {
        $supplier = Supplier::findOrFail($id);
        Gate::authorize('update', $supplier);
        $this->editingId = $id;
        $this->fill([
            'name' => $supplier->name,
            'documentType' => $supplier->document_type,
            'documentNumber' => $supplier->document_number,
            'phone' => $supplier->phone,
            'email' => $supplier->email,
            'address' => $supplier->address,
            'isActive' => $supplier->is_active,
        ]);
        $this->showModal = true;
        $this->dispatch('open-modal', 'supplier-record');
    }

    public function save(SupplierService $service): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'documentType' => ['nullable', 'string', 'max:50'],
            'documentNumber' => ['nullable', 'string', 'max:100', 'unique:suppliers,document_number,'.$this->editingId],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'isActive' => ['boolean'],
        ]);
        $data = ['name' => $this->name, 'document_type' => $this->documentType, 'document_number' => $this->documentNumber, 'phone' => $this->phone, 'email' => $this->email, 'address' => $this->address, 'is_active' => $this->isActive];
        if ($this->editingId) {
            $supplier = Supplier::findOrFail($this->editingId);
            Gate::authorize('update', $supplier);
            $service->update($supplier, $data);
        } else {
            Gate::authorize('create', Supplier::class);
            $service->create($data);
        }
        $this->closeModal();
        session()->flash('success', 'Proveedor guardado correctamente.');
    }

    public function toggle(int $id): void
    {
        $supplier = Supplier::findOrFail($id);
        Gate::authorize('update', $supplier);
        $supplier->update(['is_active' => ! $supplier->is_active]);
    }

    public function delete(int $id): void
    {
        $supplier = Supplier::findOrFail($id);
        Gate::authorize('delete', $supplier);
        $supplier->delete();
        session()->flash('success', 'Proveedor eliminado.');
    }

    public function restore(int $id): void
    {
        $supplier = Supplier::withTrashed()->findOrFail($id);
        Gate::authorize('update', $supplier);
        $supplier->restore();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->dispatch('close-modal', 'supplier-record');
        $this->resetForm();
    }

    public function render()
    {
        $suppliers = Supplier::query()
            ->withCount('purchases')
            ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$this->search.'%')->orWhere('document_number', 'like', '%'.$this->search.'%')))
            ->when($this->status !== '', fn ($query) => $query->where('is_active', $this->status === 'active'))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.supplier-index', compact('suppliers'))->layout('layouts.app');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'documentType', 'documentNumber', 'phone', 'email', 'address']);
        $this->isActive = true;
    }
}
