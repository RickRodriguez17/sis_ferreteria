<?php

namespace App\Livewire;

use App\Domain\Enums\PaymentMethod;
use App\Models\CashSession;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\PaymentAccount;
use App\Services\CreditService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class PaymentForm extends Component
{
    public ?Credit $credit = null;

    public string $amount = '';

    public string $method = 'cash';

    public string $paidAt = '';

    public string $notes = '';

    public string $accountId = '';

    public function mount(?Credit $credit = null): void
    {
        $this->credit = $credit;
        $this->paidAt = now()->format('Y-m-d\TH:i');
    }

    #[On('credit-payment-open')]
    public function open(int $creditId): void
    {
        Gate::authorize('create', CreditPayment::class);
        $this->credit = Credit::findOrFail($creditId);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->amount = '';
        $this->method = PaymentMethod::Cash->value;
        $this->paidAt = now()->format('Y-m-d\TH:i');
        $this->notes = '';
        $this->accountId = '';
        $this->dispatch('open-modal', 'credit-payment');
    }

    public function save(CreditService $service): void
    {
        Gate::authorize('create', CreditPayment::class);
        $this->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'in:cash,qr,transfer'],
            'paidAt' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'accountId' => ['nullable', 'integer', 'exists:payment_accounts,id'],
        ], [], [
            'amount' => 'monto',
            'method' => 'forma de pago',
            'paidAt' => 'fecha del cobro',
            'notes' => 'observaciones',
        ]);

        if (! $this->credit) {
            $this->addError('amount', 'Selecciona un crédito.');

            return;
        }

        $cashSession = CashSession::query()->open()->latest('opened_at')->first();
        $account = $this->method !== PaymentMethod::Cash->value && $this->accountId !== '' ? PaymentAccount::query()->active()->find($this->accountId) : null;
        if ($this->method !== PaymentMethod::Cash->value && ! $account) {
            $this->addError('accountId', 'Selecciona una cuenta activa para este método de pago.');

            return;
        }

        try {
            $service->registerPayment(
                $this->credit,
                $this->amount,
                $this->method,
                $cashSession,
                $this->notes !== '' ? $this->notes : null,
                Carbon::parse($this->paidAt),
                $account,
            );
            $this->dispatch('payment-registered', creditId: $this->credit->id);
            $this->dispatch('close-modal', 'credit-payment');
            session()->flash('success', 'Cobro registrado correctamente.');
            $this->reset(['amount', 'notes']);
        } catch (Throwable $exception) {
            $this->addError('amount', match (true) {
                $exception instanceof \InvalidArgumentException => $exception->getMessage(),
                default => 'No fue posible registrar el cobro. Verifica el saldo y la caja abierta.',
            });
        }
    }

    public function render()
    {
        return view('livewire.payment-form');
    }
}
