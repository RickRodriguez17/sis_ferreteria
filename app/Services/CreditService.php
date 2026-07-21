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
use Illuminate\Support\Facades\DB;

class CreditService
{
    public function outstandingBalance(Customer $customer): string
    {
        return (string) $customer->credits()->sum('balance');
    }

    public function registerPayment(Credit $credit, float|int|string $amount, PaymentMethod|string $method, ?CashSession $cashSession = null): CreditPayment
    {
        $payment = DB::transaction(function () use ($credit, $amount, $method, $cashSession): CreditPayment {
            $credit = Credit::query()->lockForUpdate()->findOrFail($credit->id);
            if (bccomp((string) $amount, (string) $credit->balance, 2) > 0 || bccomp((string) $amount, '0', 2) <= 0) {
                throw new \InvalidArgumentException('Payment amount must be positive and no greater than the credit balance.');
            }
            if ($cashSession && $cashSession->status !== CashSessionStatus::Open) {
                throw new CashSessionClosedException('Cannot register a payment in a closed cash session.');
            }
            $method = $method instanceof PaymentMethod ? $method : PaymentMethod::from($method);
            $paid = bcadd((string) $credit->paid_amount, (string) $amount, 2);
            $balance = bcsub((string) $credit->original_amount, $paid, 2);
            $credit->update(['paid_amount' => $paid, 'balance' => $balance, 'status' => bccomp($balance, '0', 2) === 0 ? CreditStatus::Paid : CreditStatus::Partial]);
            $payment = $credit->payments()->create(['amount' => $amount, 'method' => $method, 'cash_session_id' => $cashSession?->id, 'paid_at' => now(), 'created_by' => auth()->id()]);
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
        return Credit::query()->where('balance', '>', 0)->whereDate('due_date', '<', now())->update(['status' => CreditStatus::Overdue]);
    }
}
