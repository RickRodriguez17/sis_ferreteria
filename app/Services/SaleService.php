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
use App\Models\CashSession;
use App\Models\Credit;
use App\Models\PaymentAccount;
use App\Models\Sale;
use App\Services\Support\CodeGenerator;
use App\Services\Support\UnitConverter;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly UnitConverter $converter,
        private readonly CodeGenerator $codes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function register(array $data, array $items): Sale
    {
        return DB::transaction(function () use ($data, $items): Sale {
            $paymentMethod = $data['payment_method'] ?? PaymentMethod::Cash;
            $paymentAccount = isset($data['payment_account_id']) ? PaymentAccount::find($data['payment_account_id']) : null;
            unset($data['payment_account_id']);
            unset($data['payment_method']);
            $data['code'] ??= $this->codes->document('sale');
            $data['status'] ??= SaleStatus::Completed;
            $data['subtotal'] ??= collect($items)->sum('subtotal');
            $data['discount'] ??= 0;
            $data['total'] ??= max(0, (float) $data['subtotal'] - (float) $data['discount']);

            if (($data['cash_session_id'] ?? null) === null
                && in_array($data['payment_type'] ?? null, [PaymentType::Cash->value, PaymentType::Mixed->value], true)) {
                $data['cash_session_id'] = CashSession::query()->open()->latest('opened_at')->value('id');
            }

            unset($data['items']);
            $sale = Sale::create($data);
            $sale->items()->createMany($items);

            return $this->confirm($sale, $paymentMethod, $paymentAccount);
        });
    }

    public function confirm(Sale $sale, PaymentMethod|string $cashPaymentMethod = PaymentMethod::Cash, ?PaymentAccount $paymentAccount = null): Sale
    {
        $result = DB::transaction(function () use ($sale, $cashPaymentMethod, $paymentAccount): Sale {
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
                    'method' => $cashPaymentMethod,
                    'payment_account_id' => $paymentAccount?->id,
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
