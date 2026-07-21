<?php

namespace App\Livewire;

use App\Models\PaymentAccount;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Throwable;

class PaymentAccountIndex extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $type = 'qr';

    public string $details = '';

    public bool $isActive = true;

    public function mount(): void
    {
        Gate::authorize('viewAny', PaymentAccount::class);
    }

    public function create(): void
    {
        Gate::authorize('create', PaymentAccount::class);
        $this->resetForm();
        $this->dispatch('open-modal', 'payment-account');
    }

    public function edit(int $id): void
    {
        Gate::authorize('update', PaymentAccount::findOrFail($id));
        $account = PaymentAccount::findOrFail($id);
        $this->editingId = $account->id;
        $this->name = $account->name;
        $this->type = $account->type;
        $this->details = $account->details ?? '';
        $this->isActive = $account->is_active;
        $this->dispatch('open-modal', 'payment-account');
    }

    public function save(): void
    {
        Gate::authorize($this->editingId ? 'update' : 'create', $this->editingId ? PaymentAccount::findOrFail($this->editingId) : PaymentAccount::class);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:qr,transfer,bank'],
            'details' => ['nullable', 'string', 'max:255'],
            'isActive' => ['boolean'],
        ], [], ['name' => 'nombre', 'type' => 'tipo', 'details' => 'detalles', 'isActive' => 'estado']);
        $account = $this->editingId ? PaymentAccount::findOrFail($this->editingId) : new PaymentAccount;
        $account->fill(['name' => $data['name'], 'type' => $data['type'], 'details' => $data['details'], 'is_active' => $data['isActive']])->save();
        $this->dispatch('close-modal', 'payment-account');
        session()->flash('success', 'Cuenta de cobro guardada correctamente.');
        $this->resetForm();
    }

    public function toggle(int $id): void
    {
        $account = PaymentAccount::findOrFail($id);
        Gate::authorize('update', $account);
        $account->update(['is_active' => ! $account->is_active]);
        session()->flash('success', 'Estado de la cuenta actualizado.');
    }

    public function delete(int $id): void
    {
        $account = PaymentAccount::findOrFail($id);
        Gate::authorize('delete', $account);
        try {
            $account->delete();
            session()->flash('success', 'Cuenta eliminada correctamente.');
        } catch (Throwable) {
            $this->addError('delete', 'No fue posible eliminar la cuenta.');
        }
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'details']);
        $this->type = 'qr';
        $this->isActive = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.payment-account-index', ['accounts' => PaymentAccount::query()->orderBy('name')->get()])->layout('layouts.app');
    }
}
