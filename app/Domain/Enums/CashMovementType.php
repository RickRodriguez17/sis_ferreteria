<?php

namespace App\Domain\Enums;

enum CashMovementType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Sale = 'sale';
    case CreditPayment = 'credit_payment';
}
