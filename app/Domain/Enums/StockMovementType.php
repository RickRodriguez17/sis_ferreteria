<?php

namespace App\Domain\Enums;

enum StockMovementType: string
{
    case PurchaseReception = 'purchase_reception';
    case Sale = 'sale';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Adjustment = 'adjustment';
    case CustomerReturn = 'customer_return';
    case SupplierReturn = 'supplier_return';
}
