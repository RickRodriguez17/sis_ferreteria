<?php

namespace App\Livewire;

use App\Domain\Enums\CustomerType;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class CustomerForm extends Component
{
    public bool $inModal = false;

    public ?Customer $customer = null;

    public string $type = 'registered';

    public string $name = '';

    public ?string $documentType = null;

    public ?string $documentNumber = null;

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $address = null;

    public string $creditLimit = '0';

    public bool $isActive = true;

    public function mount(?Customer $customer = null, bool $inModal = false): void
    {
        $this->inModal = $inModal;
        $this->customer = $customer?->exists ? $customer : null;

        if ($this->customer) {
            Gate::authorize('update', $this->customer);
            $this->fillFromCustomer($this->customer);

            return;
        }

        Gate::authorize('create', Customer::class);
    }

    #[On('customer-form-open')]
    public function openCustomer(int|string|null $id = null): void
    {
        $this->resetForm();

        if ($id !== null) {
            $customer = Customer::findOrFail($id);
            Gate::authorize('update', $customer);
            $this->customer = $customer;
            $this->fillFromCustomer($customer);
        } else {
            Gate::authorize('create', Customer::class);
        }

        $this->resetErrorBag();
        $this->dispatch('open-modal', 'customer-record');
    }

    public function save(CustomerService $service): void
    {
        $this->validate();
        $data = [
            'type' => $this->type,
            'name' => $this->name,
            'document_type' => $this->documentType,
            'document_number' => $this->documentNumber,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'credit_limit' => $this->creditLimit,
            'is_active' => $this->isActive,
        ];

        if ($this->customer) {
            Gate::authorize('update', $this->customer);
            $customer = $service->update($this->customer, $data);
            $event = 'customer-updated';
            $message = 'Cliente actualizado correctamente.';
        } else {
            Gate::authorize('create', Customer::class);
            $customer = $service->create($data);
            $event = 'customer-created';
            $message = 'Cliente creado correctamente.';
        }

        $this->dispatch($event, customerId: $customer->id);
        session()->flash('success', $message);

        if ($this->inModal) {
            $this->dispatch('close-modal', 'customer-record');
            $this->resetForm();

            return;
        }

        $this->redirectRoute('customers.show', $customer);
    }

    public function cancel(): void
    {
        if ($this->inModal) {
            $this->dispatch('close-modal', 'customer-record');
        } else {
            $this->redirectRoute('customers.index');
        }

        $this->resetForm();
    }

    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_map(static fn (CustomerType $type): string => $type->value, CustomerType::cases()))],
            'name' => ['required', 'string', 'max:255'],
            'documentType' => ['nullable', 'string', 'max:50'],
            'documentNumber' => [
                Rule::requiredIf($this->type === CustomerType::Registered->value),
                'nullable',
                'string',
                'max:100',
                Rule::unique('customers', 'document_number')->ignore($this->customer?->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'creditLimit' => ['nullable', 'numeric', 'min:0'],
            'isActive' => ['boolean'],
        ];
    }

    public function render()
    {
        return view('livewire.customer-form', [
            'types' => CustomerType::cases(),
        ])->layout('layouts.app');
    }

    private function fillFromCustomer(Customer $customer): void
    {
        $this->fill([
            'type' => (string) $customer->getRawOriginal('type'),
            'name' => $customer->name,
            'documentType' => $customer->document_type,
            'documentNumber' => $customer->document_number,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'address' => $customer->address,
            'creditLimit' => (string) ($customer->credit_limit ?? '0'),
            'isActive' => $customer->is_active,
        ]);
    }

    private function resetForm(): void
    {
        $this->customer = null;
        $this->name = '';
        $this->documentType = null;
        $this->documentNumber = null;
        $this->phone = null;
        $this->email = null;
        $this->address = null;
        $this->type = CustomerType::Registered->value;
        $this->creditLimit = '0';
        $this->isActive = true;
    }
}
