<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Services\CreditService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class CustomerShow extends Component
{
    public Customer $customer;

    public string $outstandingBalance = '0.00';

    public function mount(Customer $customer, CreditService $creditService): void
    {
        Gate::authorize('view', $customer);
        $this->customer = $customer->load([
            'sales',
            'quotations',
            'credits.sale',
            'credits.payments.creator',
            'credits.payments.credit.sale',
        ]);
        $this->outstandingBalance = $creditService->outstandingBalance($customer);
    }

    public function render()
    {
        $payments = $this->customer->credits
            ->flatMap(fn ($credit) => $credit->payments)
            ->sortByDesc('paid_at')
            ->values();

        return view('livewire.customer-show', [
            'payments' => $payments,
        ])->layout('layouts.app');
    }
}
