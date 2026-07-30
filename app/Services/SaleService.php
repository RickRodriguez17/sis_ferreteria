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
use Illuminate\Support\Facades\Gate;

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
            $paymentType = $data['payment_type'] instanceof PaymentType
                ? $data['payment_type']->value
                : ($data['payment_type'] ?? null);
            $hasPendingPrice = collect($items)->contains(fn (array $item): bool => (bool) ($item['price_pending'] ?? false));
            if ($hasPendingPrice && $paymentType !== PaymentType::Credit->value) {
                throw new \InvalidArgumentException('El precio pendiente solo está permitido en ventas a crédito.');
            }
            $items = array_map(function (array $item): array {
                $pending = (bool) ($item['price_pending'] ?? false);
                $unitPrice = $pending ? 0 : (float) ($item['unit_price'] ?? 0);

                return [
                    ...$item,
                    'unit_price' => $unitPrice,
                    'subtotal' => $pending ? 0 : (float) ($item['quantity'] ?? 0) * $unitPrice,
                    'price_pending' => $pending,
                ];
            }, $items);
            $paymentMethod = $data['payment_method'] ?? PaymentMethod::Cash;
            $paymentAccount = isset($data['payment_account_id']) ? PaymentAccount::find($data['payment_account_id']) : null;
            unset($data['payment_account_id']);
            unset($data['payment_method']);
            $data['code'] ??= $this->codes->document('sale');
            $data['status'] ??= SaleStatus::Completed;
            $data['subtotal'] = collect($items)->sum('subtotal');
            $data['discount'] ??= 0;
            $data['total'] = max(0, (float) $data['subtotal'] - (float) $data['discount']);

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

    /**
     * @param  array<int|string, mixed>  $prices
     */
    public function definePendingPrices(Sale $sale, array $prices): Sale
    {
        Gate::authorize('prices.update');

        return DB::transaction(function () use ($sale, $prices): Sale {
            $sale = Sale::query()->lockForUpdate()->with(['items', 'credit', 'customer'])->findOrFail($sale->id);
            if ($sale->status !== SaleStatus::Completed || $sale->payment_type !== PaymentType::Credit) {
                throw new \InvalidArgumentException('Solo se pueden definir precios pendientes de ventas a crédito completadas.');
            }

            $pendingItems = $sale->items->where('price_pending', true);
            if ($pendingItems->isEmpty()) {
                throw new \InvalidArgumentException('La venta no tiene líneas con precio pendiente.');
            }
            foreach ($pendingItems as $item) {
                $value = $prices[$item->id] ?? null;
                if (! is_numeric($value) || (float) $value < 0) {
                    throw new \InvalidArgumentException('Todos los precios pendientes deben ser números mayores o iguales a cero.');
                }
            }

            $oldTotal = (float) $sale->total;
            foreach ($pendingItems as $item) {
                $unitPrice = round((float) $prices[$item->id], 2);
                $item->update([
                    'unit_price' => $unitPrice,
                    'subtotal' => round((float) $item->quantity * $unitPrice, 2),
                    'price_pending' => false,
                ]);
            }
            $sale->load('items');
            $subtotal = (float) $sale->items->sum('subtotal');
            $total = max(0, $subtotal - (float) $sale->discount);
            $sale->update(['subtotal' => $subtotal, 'total' => $total]);

            $credit = $sale->credit;
            if ($credit) {
                $credit = Credit::query()->lockForUpdate()->findOrFail($credit->id);
                $delta = bcsub((string) $total, (string) $oldTotal, 2);
                $original = bcadd((string) $credit->original_amount, $delta, 2);
                $balance = max('0.00', bcadd((string) $credit->balance, $delta, 2));
                $customer = $sale->customer ? $sale->customer->newQuery()->lockForUpdate()->findOrFail($sale->customer->id) : null;
                if ($customer?->credit_limit !== null) {
                    $otherBalance = $customer->credits()
                        ->where('id', '!=', $credit->id)
                        ->whereIn('status', [CreditStatus::Open, CreditStatus::Partial, CreditStatus::Overdue])
                        ->sum('balance');
                    if (bccomp(bcadd((string) $otherBalance, (string) $balance, 2), (string) $customer->credit_limit, 2) > 0) {
                        throw new CreditLimitExceededException('Definir estos precios supera el límite de crédito disponible del cliente.');
                    }
                }
                $pending = $sale->items()->where('price_pending', true)->exists();
                $status = bccomp($balance, '0', 2) === 0
                    ? ($pending ? (bccomp((string) $original, '0', 2) === 0 ? CreditStatus::Open : CreditStatus::Partial) : CreditStatus::Paid)
                    : ($credit->paid_amount > 0 ? CreditStatus::Partial : CreditStatus::Open);
                $credit->update([
                    'original_amount' => max('0.00', $original),
                    'balance' => $balance,
                    'status' => $status,
                ]);
            }

            return $sale->fresh(['items', 'credit']);
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
