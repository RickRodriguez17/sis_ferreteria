<?php

namespace App\Services;

use App\Domain\Enums\PurchaseStatus;
use App\Exceptions\PurchaseCannotBeCancelledException;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Services\Support\CodeGenerator;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(private readonly CodeGenerator $codes) {}

    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data): Purchase {
            $items = $data['items'] ?? [];
            unset($data['items']);
            $data['code'] ??= $this->codes->document('purchase');
            $data['status'] ??= PurchaseStatus::Pending;
            $data['total'] ??= collect($items)->sum(fn (array $item): float => (float) ($item['subtotal'] ?? ((float) $item['quantity_ordered'] * (float) $item['unit_cost'])));
            $purchase = Purchase::create($data);
            $purchase->items()->createMany($items);

            return $purchase->fresh('items');
        });
    }

    public function recalcStatus(Purchase $purchase): Purchase
    {
        $purchase->load('items');
        $ordered = $purchase->items->sum(fn ($item): string => (string) $item->quantity_ordered);
        $received = $purchase->items->sum(fn ($item): string => (string) $item->quantity_received);
        $status = bccomp((string) $received, '0', 4) === 0
            ? PurchaseStatus::Pending
            : (bccomp((string) $received, (string) $ordered, 4) >= 0 ? PurchaseStatus::Completed : PurchaseStatus::Partial);
        $purchase->update(['status' => $status]);

        return $purchase;
    }

    public function update(Purchase $purchase, array $data): Purchase
    {
        return DB::transaction(function () use ($purchase, $data): Purchase {
            $purchase->load('items');
            if (in_array($purchase->status, [PurchaseStatus::Completed, PurchaseStatus::Cancelled], true)) {
                throw new PurchaseCannotBeCancelledException('Completed or cancelled purchases cannot be edited.');
            }
            $items = $data['items'] ?? [];
            unset($data['items']);
            $purchase->update($data);
            foreach ($items as $item) {
                $purchaseItem = $purchase->items->firstWhere('id', $item['id'] ?? null);
                if ($purchaseItem) {
                    if (bccomp((string) $item['quantity_ordered'], (string) $purchaseItem->quantity_received, 4) < 0) {
                        throw new PurchaseCannotBeCancelledException('Ordered quantity cannot be lower than received quantity.');
                    }
                    $purchaseItem->update([
                        'quantity_ordered' => $item['quantity_ordered'],
                        'unit_cost' => $item['unit_cost'],
                        'subtotal' => $item['subtotal'],
                    ]);
                } else {
                    $purchase->items()->create($item);
                }
            }

            return $purchase->fresh('items');
        });
    }

    public function cancel(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase): Purchase {
            $purchase->load('items');
            if (bccomp((string) $purchase->items->sum(fn ($item): string => (string) $item->quantity_received), '0', 4) > 0) {
                throw new PurchaseCannotBeCancelledException('A purchase with receptions cannot be cancelled.');
            }
            if ($purchase->status === PurchaseStatus::Completed) {
                throw new PurchaseCannotBeCancelledException('A completed purchase cannot be cancelled.');
            }
            $purchase->update(['status' => PurchaseStatus::Cancelled]);

            return $purchase->fresh();
        });
    }

    public function latestSupplierForProduct(int $productId): ?Supplier
    {
        return Supplier::query()
            ->join('purchases', 'purchases.supplier_id', '=', 'suppliers.id')
            ->join('purchase_items', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchase_items.product_id', $productId)
            ->orderByDesc('purchases.created_at')
            ->select('suppliers.*')
            ->first();
    }

    public function latestCostForProduct(int $productId): ?string
    {
        return PurchaseItem::query()
            ->where('product_id', $productId)
            ->latest()
            ->value('unit_cost');
    }
}
