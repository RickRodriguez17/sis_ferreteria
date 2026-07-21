<?php

namespace App\Livewire;

use App\Domain\Enums\CashMovementType;
use App\Domain\Enums\PaymentMethod;
use App\Exceptions\CashSessionClosedException;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\PaymentAccount;
use App\Services\CashService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Throwable;

class CashRegisterPanel extends Component
{
    public string $openingAmount = '0';

    public string $countedAmount = '';

    public string $incomeAmount = '';

    public string $incomeMethod = 'cash';

    public string $incomeAccountId = '';

    public string $incomeDescription = '';

    public string $expenseAmount = '';

    public string $expenseMethod = 'cash';

    public string $expenseAccountId = '';

    public string $expenseDescription = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', CashSession::class);
    }

    public function openSession(CashService $service): void
    {
        Gate::authorize('open', CashSession::class);
        $this->validate(['openingAmount' => ['required', 'numeric', 'min:0']], [], ['openingAmount' => 'monto inicial']);
        $register = CashRegister::query()->active()->firstOrFail();

        try {
            $service->open($register, $this->openingAmount);
            session()->flash('success', 'Caja abierta correctamente.');
        } catch (Throwable $exception) {
            $this->addError('openingAmount', $exception->getMessage());
        }
    }

    public function closeSession(CashService $service): void
    {
        $session = $this->openCashSession();
        Gate::authorize('close', $session);
        $this->validate(['countedAmount' => ['required', 'numeric', 'min:0']], [], ['countedAmount' => 'monto contado']);

        try {
            $service->close($session, $this->countedAmount);
            session()->flash('success', 'Caja cerrada correctamente.');
            $this->countedAmount = '';
        } catch (CashSessionClosedException $exception) {
            $this->addError('countedAmount', 'La sesión de caja ya está cerrada.');
        }
    }

    public function registerIncome(CashService $service): void
    {
        $this->registerMovement($service, CashMovementType::Income);
    }

    public function registerExpense(CashService $service): void
    {
        $this->registerMovement($service, CashMovementType::Expense);
    }

    private function registerMovement(CashService $service, CashMovementType $type): void
    {
        $session = $this->openCashSession();
        Gate::authorize('create', CashMovement::class);
        $prefix = $type === CashMovementType::Income ? 'income' : 'expense';
        $this->validate([
            "{$prefix}Amount" => ['required', 'numeric', 'gt:0'],
            "{$prefix}Method" => ['required', 'in:cash,qr,transfer'],
            "{$prefix}AccountId" => ['nullable', 'integer', 'exists:payment_accounts,id'],
            "{$prefix}Description" => ['nullable', 'string', 'max:255'],
        ], [], [
            "{$prefix}Amount" => 'monto',
            "{$prefix}Method" => 'método',
            "{$prefix}AccountId" => 'cuenta',
            "{$prefix}Description" => 'descripción',
        ]);
        $method = $this->{$prefix.'Method'};
        $accountId = $this->{$prefix.'AccountId'};
        $account = $method !== PaymentMethod::Cash->value && $accountId !== '' ? PaymentAccount::query()->active()->find($accountId) : null;
        if ($method !== PaymentMethod::Cash->value && ! $account) {
            $this->addError($prefix.'AccountId', 'Selecciona una cuenta activa para este método.');

            return;
        }

        try {
            $method === PaymentMethod::Cash->value
                ? $service->{$type === CashMovementType::Income ? 'income' : 'expense'}($session, $this->{$prefix.'Amount'}, $method, $this->{$prefix.'Description'}, null)
                : $service->{$type === CashMovementType::Income ? 'income' : 'expense'}($session, $this->{$prefix.'Amount'}, $method, $this->{$prefix.'Description'}, $account);
            session()->flash('success', $type === CashMovementType::Income ? 'Ingreso registrado correctamente.' : 'Egreso registrado correctamente.');
            $this->reset([$prefix.'Amount', $prefix.'AccountId', $prefix.'Description']);
        } catch (CashSessionClosedException) {
            $this->addError($prefix.'Amount', 'No se puede operar sobre una caja cerrada.');
        }
    }

    private function openCashSession(): CashSession
    {
        return CashSession::query()->open()->latest('opened_at')->firstOrFail();
    }

    public function render()
    {
        $session = CashSession::query()->with(['register', 'opener'])->open()->latest('opened_at')->first();
        $accounts = PaymentAccount::query()->active()->orderBy('name')->get();
        $methods = collect(PaymentMethod::cases())->mapWithKeys(function (PaymentMethod $method) use ($session): array {
            return [$method->value => $session ? (string) $session->movements()->where('method', $method)->sum('amount') : '0.00'];
        });

        return view('livewire.cash-register-panel', [
            'session' => $session,
            'register' => CashRegister::query()->active()->first(),
            'expected' => $session ? app(CashService::class)->expectedAmount($session) : '0.00',
            'methods' => $methods,
            'accounts' => $accounts,
        ])->layout('layouts.app');
    }
}
