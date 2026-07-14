<?php

namespace App\Services;

use App\Domain\Enums\CashMovementType;
use App\Domain\Enums\CreditStatus;
use App\Domain\Enums\MovementDirection;
use App\Domain\Enums\PaymentMethod;
use App\Domain\Enums\PaymentType;
use App\Domain\Enums\SaleStatus;
use App\Domain\Enums\StockMovementType;
use App\Events\SaleConfirmed;
use App\Exceptions\CreditLimitExceededException;
use App\Models\CashMovement;
use App\Models\Credit;
use App\Models\Sale;
use App\Services\Support\UnitConverter;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly UnitConverter $converter,
    ) {}

    public function confirm(Sale $sale): Sale
    {
        $result = DB::transaction(function () use ($sale): Sale {
            $sale->load(['items.presentation', 'items.product', 'location', 'customer', 'cashSession']);
            if ($sale->status === SaleStatus::Completed && $sale->stockMovements()->exists()) {
                return $sale;
            }

            foreach ($sale->items as $item) {
                $baseQuantity = $item->presentation
                    ? $this->converter->toBase($item->quantity, $item->presentation->equivalence)
                    : (string) $item->quantity;
                $item->update(['base_quantity' => $baseQuantity]);
                $this->inventory->postMovement(
                    $item->product,
                    $sale->location,
                    StockMovementType::Sale,
                    MovementDirection::Out,
                    $baseQuantity,
                    $item->product->cost,
                    $sale,
                );
            }

            if (in_array($sale->payment_type, [PaymentType::Credit, PaymentType::Mixed], true)) {
                if (! $sale->customer) {
                    throw new CreditLimitExceededException('A customer is required for credit sales.');
                }
                $openBalance = $sale->customer->credits()->whereIn('status', [CreditStatus::Open, CreditStatus::Partial, CreditStatus::Overdue])->sum('balance');
                if ($sale->customer->credit_limit !== null && bccomp(bcadd((string) $openBalance, (string) $sale->total, 2), (string) $sale->customer->credit_limit, 2) > 0) {
                    throw new CreditLimitExceededException('Customer credit limit exceeded.');
                }
                Credit::create([
                    'customer_id' => $sale->customer_id,
                    'sale_id' => $sale->id,
                    'original_amount' => $sale->total,
                    'paid_amount' => 0,
                    'balance' => $sale->total,
                    'status' => CreditStatus::Open,
                ]);
            }

            if ($sale->cash_session_id && in_array($sale->payment_type, [PaymentType::Cash, PaymentType::Mixed], true)) {
                CashMovement::create([
                    'cash_session_id' => $sale->cash_session_id,
                    'type' => CashMovementType::Sale,
                    'method' => PaymentMethod::Cash,
                    'amount' => $sale->total,
                    'reference_type' => $sale->getMorphClass(),
                    'reference_id' => $sale->id,
                    'created_by' => auth()->id(),
                ]);
            }

            $sale->update(['status' => SaleStatus::Completed]);

            return $sale->fresh(['items', 'credit']);
        });

        SaleConfirmed::dispatch($result);

        return $result;
    }

    public function cancel(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale): Sale {
            $sale->load(['items.product', 'location']);
            foreach ($sale->items as $item) {
                $this->inventory->postMovement($item->product, $sale->location, StockMovementType::CustomerReturn, MovementDirection::In, $item->base_quantity, $item->product->cost, $sale, 'Sale cancellation');
            }
            $sale->update(['status' => SaleStatus::Cancelled]);

            return $sale;
        });
    }
}
