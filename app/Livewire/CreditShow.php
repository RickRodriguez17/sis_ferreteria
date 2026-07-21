<?php

namespace App\Livewire;

use App\Models\Credit;
use App\Models\CreditPayment;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class CreditShow extends Component
{
    public Credit $credit;

    public function mount(Credit $credit): void
    {
        Gate::authorize('view', $credit);
        $this->credit = $credit->load([
            'customer',
            'sale.items.product',
            'payments.creator',
            'payments.cashSession',
        ]);
    }

    public function openPayment(): void
    {
        Gate::authorize('create', CreditPayment::class);
        $this->dispatch('credit-payment-open', creditId: $this->credit->id);
    }

    #[On('payment-registered')]
    public function refreshCredit(int $creditId): void
    {
        if ($creditId === $this->credit->id) {
            $this->credit = $this->credit->fresh([
                'customer',
                'sale.items.product',
                'payments.creator',
                'payments.cashSession',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.credit-show')->layout('layouts.app');
    }
}
