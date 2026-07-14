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
    public function __construct(private readonly CodeGenerator $codes) {}

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
            $sale = Sale::create($saleData);
            $sale->items()->createMany($quotation->items->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'presentation_id' => $item->presentation_id,
                'quantity' => $item->quantity,
                'base_quantity' => 0,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ])->all());
            $quotation->update(['status' => QuotationStatus::Converted]);

            return $sale->fresh('items');
        });
    }
}
