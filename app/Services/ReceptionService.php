<?php

namespace App\Services;

use App\Domain\Enums\MovementDirection;
use App\Domain\Enums\StockMovementType;
use App\Events\ReceptionPosted;
use App\Models\Product;
use App\Models\Reception;
use Illuminate\Support\Facades\DB;

class ReceptionService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly PurchaseService $purchases,
    ) {}

    public function post(Reception $reception): Reception
    {
        $result = DB::transaction(function () use ($reception): Reception {
            $reception->load(['items.purchaseItem.purchase', 'items.product', 'location']);
            foreach ($reception->items as $item) {
                $purchaseItem = $item->purchaseItem;
                $newReceived = bcadd((string) $purchaseItem->quantity_received, (string) $item->quantity, 4);
                if (bccomp($newReceived, (string) $purchaseItem->quantity_ordered, 4) > 0) {
                    throw new \InvalidArgumentException("Reception exceeds ordered quantity for purchase item {$purchaseItem->id}.");
                }

                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                $oldTotal = (string) $product->inventories()->sum('quantity');
                $oldValue = bcmul($oldTotal, (string) $product->cost, 4);
                $baseQuantity = $item->product_id === $purchaseItem->product_id
                    ? (string) $item->quantity
                    : throw new \InvalidArgumentException('Reception item product does not match purchase item.');

                $this->inventory->postMovement(
                    $product,
                    $reception->location,
                    StockMovementType::PurchaseReception,
                    MovementDirection::In,
                    $baseQuantity,
                    $item->unit_cost,
                    $reception,
                );
                $purchaseItem->update(['quantity_received' => $newReceived]);

                $newTotal = bcadd($oldTotal, $baseQuantity, 4);
                if (bccomp($newTotal, '0', 4) > 0) {
                    $newValue = bcadd($oldValue, bcmul($baseQuantity, (string) $item->unit_cost, 4), 4);
                    $product->update(['cost' => bcdiv($newValue, $newTotal, 4)]);
                }
            }

            $this->purchases->recalcStatus($reception->purchase);

            return $reception->fresh(['items', 'purchase', 'location']);
        });

        ReceptionPosted::dispatch($result);

        return $result;
    }
}
