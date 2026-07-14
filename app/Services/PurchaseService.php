<?php

namespace App\Services;

use App\Domain\Enums\PurchaseStatus;
use App\Models\Purchase;
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
}
