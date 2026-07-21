<?php

namespace Tests\Feature;

use App\Livewire\QuotationShow;
use App\Livewire\SaleForm;
use App\Livewire\SaleIndex;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\QuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesQuotationModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('email', 'admin@construir.local')->firstOrFail());
    }

    public function test_cash_sale_reduces_stock_and_creates_kardex_entry(): void
    {
        $product = Product::query()->firstOrFail();
        $presentation = $product->presentations()->firstOrFail();
        $location = Location::query()->where('is_default', true)->firstOrFail();
        $before = (float) Inventory::query()->where('product_id', $product->id)->where('location_id', $location->id)->value('quantity');
        $movements = StockMovement::count();

        Livewire::test(SaleForm::class)
            ->set('locationId', (string) $location->id)
            ->set('items', [[
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'quantity' => '1',
                'unit_price' => (string) $presentation->price_without_invoice,
                'subtotal' => (string) $presentation->price_without_invoice,
            ]])
            ->call('save')
            ->assertRedirect();

        $this->assertEquals($before - (float) $presentation->equivalence, (float) Inventory::query()->where('product_id', $product->id)->where('location_id', $location->id)->value('quantity'));
        $this->assertSame($movements + 1, StockMovement::count());
        $this->assertDatabaseHas('sales', ['payment_type' => 'cash', 'status' => 'completed']);
    }

    public function test_credit_sale_creates_credit_and_obeys_limit(): void
    {
        $product = Product::query()->firstOrFail();
        $presentation = $product->presentations()->firstOrFail();
        $location = Location::query()->where('is_default', true)->firstOrFail();
        $customer = Customer::query()->where('credit_limit', '>', 0)->firstOrFail();
        $creditsBefore = Credit::query()->where('customer_id', $customer->id)->count();

        Livewire::test(SaleForm::class)
            ->set('locationId', (string) $location->id)
            ->set('customerId', (string) $customer->id)
            ->set('paymentType', 'credit')
            ->set('items', [[
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'quantity' => '1',
                'unit_price' => '10',
                'subtotal' => '10',
            ]])
            ->call('save')
            ->assertRedirect();

        $this->assertDatabaseHas('credits', ['customer_id' => $customer->id, 'balance' => '10.00']);
        $this->assertSame($creditsBefore + 1, Credit::query()->where('customer_id', $customer->id)->count());
    }

    public function test_sale_without_stock_fails_with_friendly_error(): void
    {
        $product = Product::query()->firstOrFail();
        $presentation = $product->presentations()->firstOrFail();
        $location = Location::query()->where('is_default', true)->firstOrFail();
        $before = Sale::count();

        Livewire::test(SaleForm::class)
            ->set('locationId', (string) $location->id)
            ->set('items', [[
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'quantity' => '999999',
                'unit_price' => '10',
                'subtotal' => '9999990',
            ]])
            ->call('save')
            ->assertHasErrors('items');

        $this->assertSame($before, Sale::count());
    }

    public function test_credit_sale_over_limit_is_rejected(): void
    {
        $product = Product::query()->firstOrFail();
        $presentation = $product->presentations()->firstOrFail();
        $location = Location::query()->where('is_default', true)->firstOrFail();
        $customer = Customer::factory()->create(['credit_limit' => 1]);

        Livewire::test(SaleForm::class)
            ->set('locationId', (string) $location->id)
            ->set('customerId', (string) $customer->id)
            ->set('paymentType', 'credit')
            ->set('items', [[
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'quantity' => '1',
                'unit_price' => '10',
                'subtotal' => '10',
            ]])
            ->call('save')
            ->assertHasErrors('customerId');

        $this->assertDatabaseMissing('credits', ['customer_id' => $customer->id]);
    }

    public function test_open_quotation_can_be_converted_to_sale(): void
    {
        $product = Product::query()->firstOrFail();
        $presentation = $product->presentations()->firstOrFail();
        $quotation = app(QuotationService::class)->create([
            'with_invoice' => false,
            'items' => [[
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'quantity' => '1',
                'unit_price' => '10',
                'subtotal' => '10',
            ]],
        ]);

        Livewire::test(QuotationShow::class, ['quotation' => $quotation])
            ->call('convert')
            ->assertRedirect();

        $this->assertSame('converted', $quotation->fresh()->status->value);
        $this->assertDatabaseHas('sales', ['quotation_id' => $quotation->id, 'status' => 'completed']);
    }

    public function test_cashier_cannot_cancel_sale(): void
    {
        $sale = Sale::query()->where('status', 'completed')->firstOrFail();
        $cashier = User::where('email', 'cajera@construir.local')->firstOrFail();
        $this->actingAs($cashier);

        Livewire::test(SaleIndex::class)
            ->call('cancel', $sale->id)
            ->assertForbidden();

        $this->assertSame('completed', $sale->fresh()->status->value);
    }

    public function test_sales_and_quotation_documents_can_be_downloaded(): void
    {
        $sale = Sale::query()->where('status', 'completed')->with('items')->firstOrFail();
        $product = Product::query()->firstOrFail();
        $presentation = $product->presentations()->firstOrFail();
        $quotation = app(QuotationService::class)->create([
            'items' => [[
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'quantity' => '1',
                'unit_price' => '10',
                'subtotal' => '10',
            ]],
        ]);

        $this->get(route('sales.pdf', $sale))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->get(route('quotations.pdf', $quotation))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
