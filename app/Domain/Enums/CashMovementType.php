<?php

namespace App\Domain\Enums;

enum CashMovementType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Sale = 'sale';
    case CreditPayment = 'credit_payment';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Ingreso',
            self::Expense => 'Egreso',
            self::Sale => 'Venta',
            self::CreditPayment => 'Cobro de crédito',
        };
    }
}
