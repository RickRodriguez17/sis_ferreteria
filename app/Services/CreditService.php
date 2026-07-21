<?php

namespace App\Services;

use App\Domain\Enums\CashMovementType;
use App\Domain\Enums\CashSessionStatus;
use App\Domain\Enums\CreditStatus;
use App\Domain\Enums\PaymentMethod;
use App\Events\CreditPaymentRegistered;
use App\Exceptions\CashSessionClosedException;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreditService
{
    public function outstandingBalance(Customer $customer): string
    {
        return (string) $customer->credits()->sum('balance');
    }

    public function registerPayment(Credit $credit, float|int|string $amount, PaymentMethod|string $method, ?CashSession $cashSession = null, ?string $notes = null, ?Carbon $paidAt = null): CreditPayment
    {
        $payment = DB::transaction(function () use ($credit, $amount, $method, $cashSession, $notes, $paidAt): CreditPayment {
            $credit = Credit::query()->lockForUpdate()->findOrFail($credit->id);
            if ($credit->status === CreditStatus::Cancelled) {
                throw new \InvalidArgumentException('No se puede registrar un cobro sobre un crédito anulado.');
            }
            if (bccomp((string) $amount, (string) $credit->balance, 2) > 0 || bccomp((string) $amount, '0', 2) <= 0) {
                throw new \InvalidArgumentException('El monto debe ser mayor que cero y no superar el saldo del crédito.');
            }
            if ($cashSession && $cashSession->status !== CashSessionStatus::Open) {
                throw new CashSessionClosedException('No se puede registrar el cobro en una caja cerrada.');
            }
            $method = $method instanceof PaymentMethod ? $method : PaymentMethod::from($method);
            $paid = bcadd((string) $credit->paid_amount, (string) $amount, 2);
            $balance = bcsub((string) $credit->original_amount, $paid, 2);
            $credit->update(['paid_amount' => $paid, 'balance' => $balance, 'status' => bccomp($balance, '0', 2) === 0 ? CreditStatus::Paid : CreditStatus::Partial]);
            $payment = $credit->payments()->create(['amount' => $amount, 'method' => $method, 'cash_session_id' => $cashSession?->id, 'paid_at' => $paidAt ?? now(), 'notes' => $notes, 'created_by' => auth()->id()]);
            if ($cashSession) {
                CashMovement::create(['cash_session_id' => $cashSession->id, 'type' => CashMovementType::CreditPayment, 'method' => $method, 'amount' => $amount, 'reference_type' => $payment->getMorphClass(), 'reference_id' => $payment->id, 'created_by' => auth()->id()]);
            }

            return $payment;
        });
        CreditPaymentRegistered::dispatch($payment);

        return $payment;
    }

    public function markOverdue(): int
    {
        return Credit::query()->where('balance', '>', 0)->where('status', '!=', CreditStatus::Cancelled)->whereDate('due_date', '<', now())->update(['status' => CreditStatus::Overdue]);
    }

    public function cancel(Credit $credit): Credit
    {
        return DB::transaction(function () use ($credit): Credit {
            $credit = Credit::query()->lockForUpdate()->findOrFail($credit->id);
            if ($credit->payments()->exists()) {
                throw new \InvalidArgumentException('No se puede anular un crédito que tiene cobros registrados.');
            }

            $credit->update(['status' => CreditStatus::Cancelled]);

            return $credit->fresh();
        });
    }
}
