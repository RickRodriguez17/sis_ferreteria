<?php

namespace App\Services;

use App\Domain\Enums\CashMovementType;
use App\Domain\Enums\CashSessionStatus;
use App\Domain\Enums\PaymentMethod;
use App\Exceptions\CashSessionClosedException;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use Illuminate\Support\Facades\DB;

class CashService
{
    public function open(CashRegister $register, float|int|string $openingAmount): CashSession
    {
        return DB::transaction(fn (): CashSession => CashSession::create([
            'cash_register_id' => $register->id,
            'opened_by' => auth()->id(),
            'opening_amount' => $openingAmount,
            'status' => CashSessionStatus::Open,
            'opened_at' => now(),
        ]));
    }

    public function close(CashSession $session, float|int|string $countedAmount): CashSession
    {
        return DB::transaction(function () use ($session, $countedAmount): CashSession {
            $session->refresh();
            if ($session->status !== CashSessionStatus::Open) {
                throw new CashSessionClosedException('Cash session is already closed.');
            }
            $expected = $this->expectedAmount($session);
            $difference = bcsub((string) $countedAmount, $expected, 2);

            return tap($session)->update([
                'closing_amount' => $expected,
                'counted_amount' => $countedAmount,
                'difference' => $difference,
                'closed_by' => auth()->id(),
                'status' => CashSessionStatus::Closed,
                'closed_at' => now(),
            ]);
        });
    }

    public function income(CashSession $session, float|int|string $amount, PaymentMethod|string $method, ?string $description = null): CashMovement
    {
        return $this->movement($session, CashMovementType::Income, $amount, $method, $description);
    }

    public function expense(CashSession $session, float|int|string $amount, PaymentMethod|string $method, ?string $description = null): CashMovement
    {
        return $this->movement($session, CashMovementType::Expense, $amount, $method, $description);
    }

    public function expectedAmount(CashSession $session): string
    {
        $income = $session->movements()->whereIn('type', [CashMovementType::Income, CashMovementType::Sale, CashMovementType::CreditPayment])->where('method', PaymentMethod::Cash)->sum('amount');
        $expense = $session->movements()->where('type', CashMovementType::Expense)->where('method', PaymentMethod::Cash)->sum('amount');

        return bcadd(bcsub((string) $session->opening_amount, (string) $expense, 2), (string) $income, 2);
    }

    private function movement(CashSession $session, CashMovementType $type, float|int|string $amount, PaymentMethod|string $method, ?string $description): CashMovement
    {
        if ($session->status !== CashSessionStatus::Open) {
            throw new CashSessionClosedException('Cannot record movement in a closed cash session.');
        }

        return $session->movements()->create(['type' => $type, 'method' => $method, 'amount' => $amount, 'description' => $description, 'created_by' => auth()->id()]);
    }
}
