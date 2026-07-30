<?php

namespace Tests\Feature;

use App\Domain\Enums\MovementDirection;
use App\Domain\Enums\PaymentType;
use App\Domain\Enums\SaleStatus;
use App\Domain\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\CustomerReturn;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\Reception;
use App\Models\Sale;
use App\Models\User;
use App\Services\ReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('email', 'admin@construir.local')->firstOrFail());
    }

    public function test_customer_return_reenters_stock_and_creates_kardex_entry(): void
    {
        $sale = Sale::query()->where('code', 'DEMO-CASH-001')->with('items')->firstOrFail();
        $item = $sale->items->firstOrFail();
        $before = (float) Inventory::query()->where('product_id', $item->product_id)->where('location_id', $sale->location_id)->value('quantity');

        app(ReturnService::class)->customer($sale, [['sale_item_id' => $item->id, 'quantity' => '1']]);

        $this->assertEquals($before + 1, (float) Inventory::query()->where('product_id', $item->product_id)->where('location_id', $sale->location_id)->value('quantity'));
        $this->assertDatabaseHas('stock_movements', ['type' => StockMovementType::CustomerReturn->value, 'direction' => MovementDirection::In->value, 'reference_type' => CustomerReturn::class]);
        $this->assertDatabaseHas('customer_returns', ['sale_id' => $sale->id, 'total' => '20.00']);
    }

    public function test_customer_return_cannot_exceed_pending_quantity(): void
    {
        $sale = Sale::query()->where('code', 'DEMO-CASH-001')->with('items')->firstOrFail();
        $item = $sale->items->firstOrFail();
        $service = app(ReturnService::class);
        $service->customer($sale, [['sale_item_id' => $item->id, 'quantity' => '1']]);

        $this->expectException(\InvalidArgumentException::class);
        $service->customer($sale, [['sale_item_id' => $item->id, 'quantity' => '1']]);
    }

    public function test_supplier_return_decreases_stock_and_blocks_insufficient_stock(): void
    {
        $reception = Reception::query()->with('items')->firstOrFail();
        $item = $reception->items->firstOrFail();
        $before = (float) Inventory::query()->where('product_id', $item->product_id)->where('location_id', $reception->location_id)->value('quantity');
        app(ReturnService::class)->supplier($reception, [['reception_item_id' => $item->id, 'quantity' => '1']]);
        $this->assertEquals($before - 1, (float) Inventory::query()->where('product_id', $item->product_id)->where('location_id', $reception->location_id)->value('quantity'));

        $secondItem = $reception->items->skip(1)->firstOrFail();
        Inventory::query()->where('product_id', $secondItem->product_id)->where('location_id', $reception->location_id)->update(['quantity' => 0]);

        $this->expectException(InsufficientStockException::class);
        app(ReturnService::class)->supplier($reception, [['reception_item_id' => $secondItem->id, 'quantity' => '1']]);
        $this->assertDatabaseMissing('supplier_returns', ['reception_id' => $reception->id]);
    }

    public function test_credit_customer_return_reduces_credit_balance(): void
    {
        $product = Product::query()->firstOrFail();
        $presentation = $product->presentations()->firstOrFail();
        $location = Location::query()->where('is_default', true)->firstOrFail();
        $customerModel = Customer::query()->where('email', 'cliente@construir.local')->firstOrFail();
        $sale = Sale::create(['code' => 'RETURN-CREDIT-001', 'customer_id' => $customerModel->id, 'with_invoice' => false, 'payment_type' => PaymentType::Credit, 'status' => SaleStatus::Completed, 'subtotal' => 10, 'discount' => 0, 'total' => 10, 'location_id' => $location->id]);
        $sale->items()->create(['product_id' => $product->id, 'presentation_id' => $presentation->id, 'quantity' => 1, 'base_quantity' => 1, 'unit_price' => 10, 'subtotal' => 10]);
        $sale->credit()->create(['customer_id' => $customerModel->id, 'original_amount' => 10, 'paid_amount' => 0, 'balance' => 10, 'status' => 'open']);
        $credit = Credit::query()->where('sale_id', $sale->id)->firstOrFail();

        app(ReturnService::class)->customer($sale, [['sale_item_id' => $sale->items()->firstOrFail()->id, 'quantity' => '1']]);

        $this->assertSame('0.00', (string) $credit->fresh()->balance);
        $this->assertSame('paid', $credit->fresh()->status->value);
    }

    public function test_cashier_cannot_create_supplier_returns(): void
    {
        $cashier = User::where('email', 'cajera@construir.local')->firstOrFail();

        $this->actingAs($cashier)
            ->get(route('returns.create', 'supplier'))
            ->assertForbidden();
    }
}
