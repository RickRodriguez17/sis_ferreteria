<?php

namespace Tests\Feature;

use App\Domain\Enums\PaymentMethod;
use App\Domain\Enums\PaymentType;
use App\Domain\Enums\SaleStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\CashSession;
use App\Models\Credit;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Services\CashService;
use App\Services\CreditService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_reception_creates_real_stock_and_cpp(): void
    {
        $product = Product::query()->firstOrFail();
        $this->assertGreaterThan(0, (float) $product->stockTotal());
        $this->assertGreaterThan(0, (float) $product->cost);
        $this->assertEquals(
            (float) $product->inventories()->sum('quantity'),
            (float) $product->stockMovements()->where('direction', 'in')->sum('quantity') - (float) $product->stockMovements()->where('direction', 'out')->sum('quantity'),
        );
    }

    public function test_sale_decreases_stock_and_rejects_insufficient_stock(): void
    {
        $product = Product::query()->firstOrFail();
        $location = Location::query()->firstOrFail();
        $before = (float) $product->inventories()->where('location_id', $location->id)->value('quantity');
        $sale = Sale::create(['code' => 'TEST-SALE-001', 'with_invoice' => false, 'payment_type' => PaymentType::Cash, 'status' => SaleStatus::Completed, 'subtotal' => 10, 'discount' => 0, 'total' => 10, 'location_id' => $location->id]);
        $sale->items()->create(['product_id' => $product->id, 'presentation_id' => $product->presentations()->first()->id, 'quantity' => 1, 'base_quantity' => 0, 'unit_price' => 10, 'subtotal' => 10]);
        app(SaleService::class)->confirm($sale);
        $this->assertEquals($before - 1, (float) $product->inventories()->where('location_id', $location->id)->value('quantity'));

        $this->expectException(InsufficientStockException::class);
        $failed = Sale::create(['code' => 'TEST-SALE-002', 'with_invoice' => false, 'payment_type' => PaymentType::Cash, 'status' => SaleStatus::Completed, 'subtotal' => 10, 'discount' => 0, 'total' => 10, 'location_id' => $location->id]);
        $failed->items()->create(['product_id' => $product->id, 'presentation_id' => $product->presentations()->first()->id, 'quantity' => 999, 'base_quantity' => 0, 'unit_price' => 10, 'subtotal' => 10]);
        app(SaleService::class)->confirm($failed);
    }

    public function test_credit_payment_reduces_balance(): void
    {
        $credit = Credit::query()->firstOrFail();
        $before = (float) $credit->balance;
        app(CreditService::class)->registerPayment($credit, 5, PaymentMethod::Cash);
        $this->assertEquals($before - 5, (float) $credit->fresh()->balance);
    }

    public function test_cash_session_closes_without_difference_when_counted_amount_matches(): void
    {
        $session = CashSession::query()->open()->firstOrFail();
        $expected = app(CashService::class)->expectedAmount($session);
        $closed = app(CashService::class)->close($session, $expected);
        $this->assertEquals('0.00', (string) $closed->difference);
    }
}
