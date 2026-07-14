<?php

namespace App\Services;

use App\Domain\Enums\MovementDirection;
use App\Domain\Enums\StockMovementType;
use App\Events\StockBelowMinimum;
use App\Exceptions\InsufficientStockException;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function postMovement(
        Product $product,
        Location $location,
        StockMovementType|string $type,
        MovementDirection|string $direction,
        float|int|string $quantityBase,
        float|int|string|null $unitCost = null,
        ?Model $reference = null,
        ?string $notes = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $location, $type, $direction, $quantityBase, $unitCost, $reference, $notes): StockMovement {
            $direction = $direction instanceof MovementDirection ? $direction : MovementDirection::from($direction);
            $type = $type instanceof StockMovementType ? $type : StockMovementType::from($type);
            $quantity = (string) $quantityBase;
            $inventory = Inventory::firstOrCreate(
                ['product_id' => $product->id, 'location_id' => $location->id],
                ['quantity' => 0, 'reserved_quantity' => 0],
            );
            $inventory = Inventory::query()->whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $balance = (string) $inventory->quantity;

            if ($direction === MovementDirection::Out && bccomp($balance, $quantity, 4) < 0) {
                throw new InsufficientStockException("Insufficient stock for product {$product->id} at location {$location->id}.");
            }

            $newBalance = $direction === MovementDirection::In
                ? bcadd($balance, $quantity, 4)
                : bcsub($balance, $quantity, 4);
            $inventory->update(['quantity' => $newBalance]);

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'location_id' => $location->id,
                'type' => $type,
                'direction' => $direction,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'balance_after' => $newBalance,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            if ($product->min_stock !== null && bccomp($newBalance, (string) $product->min_stock, 4) < 0) {
                StockBelowMinimum::dispatch($inventory->fresh(['product']));
            }

            return $movement;
        });
    }

    public function transfer(Product $product, Location $from, Location $to, float|int|string $quantity): array
    {
        return DB::transaction(fn (): array => [
            'out' => $this->postMovement($product, $from, StockMovementType::TransferOut, MovementDirection::Out, $quantity, null, null, "Transfer to {$to->name}"),
            'in' => $this->postMovement($product, $to, StockMovementType::TransferIn, MovementDirection::In, $quantity, null, null, "Transfer from {$from->name}"),
        ]);
    }

    public function adjust(Product $product, Location $location, float|int|string $delta, ?string $notes = null): StockMovement
    {
        $direction = bccomp((string) $delta, '0', 4) >= 0 ? MovementDirection::In : MovementDirection::Out;

        return $this->postMovement($product, $location, StockMovementType::Adjustment, $direction, ltrim((string) $delta, '-'), $product->cost, null, $notes);
    }

    public function rebuild(): void
    {
        DB::transaction(function (): void {
            StockMovement::query()->select(['product_id', 'location_id'])->distinct()->get()->each(function (StockMovement $movement): void {
                $quantity = StockMovement::query()
                    ->where('product_id', $movement->product_id)
                    ->where('location_id', $movement->location_id)
                    ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END), 0) AS quantity")
                    ->value('quantity');
                Inventory::updateOrCreate(
                    ['product_id' => $movement->product_id, 'location_id' => $movement->location_id],
                    ['quantity' => $quantity],
                );
            });
        });
    }
}
