<?php

namespace App\Services;

use App\Domain\Enums\QuotationStatus;
use App\Domain\Enums\SaleStatus;
use App\Models\Quotation;
use App\Models\Sale;
use App\Services\Support\CodeGenerator;
use Illuminate\Support\Facades\DB;

class QuotationService
{
    public function __construct(
        private readonly CodeGenerator $codes,
        private readonly SaleService $sales,
    ) {}

    public function create(array $data): Quotation
    {
        return DB::transaction(function () use ($data): Quotation {
            $items = $data['items'] ?? [];
            unset($data['items']);
            $data['code'] ??= $this->codes->document('quotation');
            $data['status'] ??= QuotationStatus::Open;
            $data['subtotal'] ??= collect($items)->sum('subtotal');
            $data['total'] ??= $data['subtotal'];
            $quotation = Quotation::create($data);
            $quotation->items()->createMany($items);

            return $quotation->fresh('items');
        });
    }

    public function convertToSale(Quotation $quotation, array $overrides = []): Sale
    {
        return DB::transaction(function () use ($quotation, $overrides): Sale {
            $quotation->load('items');
            $saleData = array_merge([
                'code' => $this->codes->document('sale'),
                'customer_id' => $quotation->customer_id,
                'quotation_id' => $quotation->id,
                'with_invoice' => $quotation->with_invoice,
                'payment_type' => 'cash',
                'status' => SaleStatus::Completed,
                'subtotal' => $quotation->subtotal,
                'discount' => 0,
                'total' => $quotation->total,
                'location_id' => $overrides['location_id'] ?? null,
                'cash_session_id' => $overrides['cash_session_id'] ?? null,
            ], $overrides);
            $items = $quotation->items->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'presentation_id' => $item->presentation_id,
                'quantity' => $item->quantity,
                'base_quantity' => 0,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ])->all();
            $sale = $this->sales->register($saleData, $items);
            $quotation->update(['status' => QuotationStatus::Converted]);

            return $sale->fresh('items');
        });
    }

    public function update(Quotation $quotation, array $data): Quotation
    {
        return DB::transaction(function () use ($quotation, $data): Quotation {
            $items = $data['items'] ?? [];
            unset($data['items']);
            $data['subtotal'] = collect($items)->sum('subtotal');
            $data['total'] = max(0, (float) $data['subtotal']);
            $quotation->update($data);
            $quotation->items()->delete();
            $quotation->items()->createMany($items);

            return $quotation->fresh('items');
        });
    }

    public function duplicate(Quotation $quotation): Quotation
    {
        return DB::transaction(function () use ($quotation): Quotation {
            $quotation->load('items');

            return $this->create([
                'customer_id' => $quotation->customer_id,
                'with_invoice' => $quotation->with_invoice,
                'valid_until' => $quotation->valid_until,
                'items' => $quotation->items->map(fn ($item): array => [
                    'product_id' => $item->product_id,
                    'presentation_id' => $item->presentation_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ])->all(),
            ]);
        });
    }
}
