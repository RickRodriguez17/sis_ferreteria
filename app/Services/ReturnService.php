<?php

namespace App\Services;

use App\Domain\Enums\MovementDirection;
use App\Domain\Enums\PaymentMethod;
use App\Domain\Enums\PaymentType;
use App\Domain\Enums\SaleStatus;
use App\Domain\Enums\StockMovementType;
use App\Models\CashSession;
use App\Models\CustomerReturn;
use App\Models\CustomerReturnItem;
use App\Models\Reception;
use App\Models\Sale;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Services\Support\CodeGenerator;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly CreditService $credits,
        private readonly CashService $cash,
        private readonly CodeGenerator $codes,
    ) {}

    /**
     * @param  array<int, array{sale_item_id:int, quantity:float|int|string}>  $items
     */
    public function customer(Sale $sale, array $items, ?string $notes = null): CustomerReturn
    {
        return DB::transaction(function () use ($sale, $items, $notes): CustomerReturn {
            $sale = Sale::query()->lockForUpdate()->with(['items.product', 'items.presentation', 'location', 'credit', 'customer'])->findOrFail($sale->id);
            if ($sale->status !== SaleStatus::Completed) {
                throw new \InvalidArgumentException('Solo se pueden devolver ventas completadas.');
            }
            if ($items === []) {
                throw new \InvalidArgumentException('Selecciona al menos un producto para devolver.');
            }

            $total = '0.00';
            $prepared = [];
            $seen = [];

            foreach ($items as $data) {
                $saleItem = $sale->items->firstWhere('id', (int) ($data['sale_item_id'] ?? 0));
                $quantity = (string) ($data['quantity'] ?? '0');
                if (! $saleItem || isset($seen[$saleItem->id]) || bccomp($quantity, '0', 4) <= 0) {
                    throw new \InvalidArgumentException('Las cantidades de devolución deben ser mayores que cero.');
                }
                $seen[$saleItem->id] = true;
                $returned = (string) CustomerReturnItem::query()
                    ->where('sale_item_id', $saleItem->id)
                    ->sum('quantity_base');
                $available = bcsub((string) $saleItem->base_quantity, $returned, 4);
                if (bccomp($quantity, $available, 4) > 0) {
                    throw new \InvalidArgumentException('La cantidad devuelta no puede superar la cantidad pendiente de devolución.');
                }

                $unitPrice = bcdiv((string) $saleItem->subtotal, (string) $saleItem->base_quantity, 4);
                $subtotal = bcmul($quantity, $unitPrice, 2);
                $prepared[] = [
                    'saleItem' => $saleItem,
                    'quantity' => $quantity,
                    'unitPrice' => $unitPrice,
                    'subtotal' => $subtotal,
                ];
                $total = bcadd($total, $subtotal, 2);
            }

            $return = CustomerReturn::create([
                'code' => $this->codes->document('customer_return'),
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'location_id' => $sale->location_id,
                'returned_at' => now(),
                'total' => $total,
                'notes' => $notes,
            ]);

            foreach ($prepared as $data) {
                $saleItem = $data['saleItem'];
                $return->items()->create([
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'presentation_id' => $saleItem->presentation_id,
                    'quantity_base' => $data['quantity'],
                    'unit_price' => $data['unitPrice'],
                    'subtotal' => $data['subtotal'],
                ]);
                $this->inventory->postMovement($saleItem->product, $sale->location, StockMovementType::CustomerReturn, MovementDirection::In, $data['quantity'], $saleItem->product->cost, $return, 'Devolución de cliente');
            }

            if ($sale->credit && in_array($sale->payment_type, [PaymentType::Credit, PaymentType::Mixed], true) && bccomp((string) $sale->credit->balance, '0', 2) > 0) {
                $this->credits->applyReturn($sale->credit, $total);
            } else {
                $session = CashSession::query()->open()->latest('opened_at')->first();
                if (! $session) {
                    throw new \InvalidArgumentException('Se requiere una sesión de caja abierta para reembolsar esta devolución.');
                }
                $this->cash->expense($session, $total, PaymentMethod::Cash, 'Reembolso por devolución '.$return->code, null, $return);
            }

            return $return->fresh(['items', 'sale', 'customer', 'location']);
        });
    }

    /**
     * @param  array<int, array{reception_item_id:int, quantity:float|int|string}>  $items
     */
    public function supplier(Reception $reception, array $items, ?string $notes = null): SupplierReturn
    {
        return DB::transaction(function () use ($reception, $items, $notes): SupplierReturn {
            $reception = Reception::query()->lockForUpdate()->with(['items.product', 'purchase.supplier', 'location'])->findOrFail($reception->id);
            if ($items === []) {
                throw new \InvalidArgumentException('Selecciona al menos un producto para devolver.');
            }

            $total = '0.00';
            $prepared = [];
            $seen = [];

            foreach ($items as $data) {
                $receptionItem = $reception->items->firstWhere('id', (int) ($data['reception_item_id'] ?? 0));
                $quantity = (string) ($data['quantity'] ?? '0');
                if (! $receptionItem || isset($seen[$receptionItem->id]) || bccomp($quantity, '0', 4) <= 0) {
                    throw new \InvalidArgumentException('Las cantidades de devolución deben ser mayores que cero.');
                }
                $seen[$receptionItem->id] = true;
                $returned = (string) SupplierReturnItem::query()
                    ->where('reception_item_id', $receptionItem->id)
                    ->sum('quantity_base');
                $available = bcsub((string) $receptionItem->quantity, $returned, 4);
                if (bccomp($quantity, $available, 4) > 0) {
                    throw new \InvalidArgumentException('La cantidad devuelta no puede superar la cantidad recibida pendiente.');
                }

                $subtotal = bcmul($quantity, (string) $receptionItem->unit_cost, 2);
                $prepared[] = [
                    'receptionItem' => $receptionItem,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
                $total = bcadd($total, $subtotal, 2);
            }

            $return = SupplierReturn::create([
                'code' => $this->codes->document('supplier_return'),
                'reception_id' => $reception->id,
                'purchase_id' => $reception->purchase_id,
                'supplier_id' => $reception->purchase?->supplier_id,
                'location_id' => $reception->location_id,
                'returned_at' => now(),
                'total' => $total,
                'notes' => trim(($notes ? $notes."\n" : '').'Cuentas por pagar no ajustadas automáticamente: no existe un mecanismo de saldo por pagar integrado.'),
            ]);

            foreach ($prepared as $data) {
                $receptionItem = $data['receptionItem'];
                $return->items()->create([
                    'reception_item_id' => $receptionItem->id,
                    'product_id' => $receptionItem->product_id,
                    'quantity_base' => $data['quantity'],
                    'unit_cost' => $receptionItem->unit_cost,
                    'subtotal' => $data['subtotal'],
                ]);
                $this->inventory->postMovement($receptionItem->product, $reception->location, StockMovementType::SupplierReturn, MovementDirection::Out, $data['quantity'], $receptionItem->unit_cost, $return, 'Devolución a proveedor');
            }

            return $return->fresh(['items', 'reception', 'purchase', 'supplier', 'location']);
        });
    }
}
