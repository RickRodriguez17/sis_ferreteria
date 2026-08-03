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

    public function label(): string
    {
        return match ($this) {
            self::PurchaseReception => 'Recepción de compra',
            self::Sale => 'Venta',
            self::TransferIn => 'Transferencia de entrada',
            self::TransferOut => 'Transferencia de salida',
            self::Adjustment => 'Ajuste',
            self::CustomerReturn => 'Devolución de cliente',
            self::SupplierReturn => 'Devolución a proveedor',
        };
    }
}
