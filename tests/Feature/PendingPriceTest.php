<?php

namespace Tests\Feature;

use App\Domain\Enums\CreditStatus;
use App\Domain\Enums\PaymentMethod;
use App\Livewire\SaleForm;
use App\Livewire\SaleShow;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\CreditService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PendingPriceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('email', 'admin@construir.local')->firstOrFail());
    }

    public function test_credit_sale_with_pending_price_delivers_stock_without_credit_amount(): void
    {
        [$product, $presentation, $location, $customer] = $this->saleData();
        $before = (float) Inventory::query()->where('product_id', $product->id)->where('location_id', $location->id)->value('quantity');

        Livewire::test(SaleForm::class)
            ->set('locationId', (string) $location->id)
            ->set('customerId', (string) $customer->id)
            ->set('paymentType', 'credit')
            ->set('items', [[
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'quantity' => '1',
                'unit_price' => '',
                'subtotal' => '0',
                'price_pending' => true,
            ]])
            ->call('save')
            ->assertRedirect();

        $sale = Sale::latest('id')->firstOrFail();
        $this->assertSame($before - (float) $presentation->equivalence, (float) Inventory::query()->where('product_id', $product->id)->where('location_id', $location->id)->value('quantity'));
        $this->assertDatabaseHas('credits', ['sale_id' => $sale->id, 'original_amount' => '0.00', 'balance' => '0.00', 'status' => 'open']);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id, 'price_pending' => 1, 'unit_price' => '0.00', 'subtotal' => '0.00']);
    }

    public function test_pending_credit_cannot_become_paid_while_price_is_pending(): void
    {
        [$sale, $credit] = $this->pendingCredit(100);
        $this->assertTrue($credit->hasPendingPriceItems());

        app(CreditService::class)->registerPayment($credit, 100, PaymentMethod::Cash);

        $this->assertSame(CreditStatus::Partial, $credit->fresh()->status);
        $this->assertSame('0.00', $credit->fresh()->balance);
        $this->assertNotSame(CreditStatus::Paid, $credit->fresh()->status);
    }

    public function test_defining_prices_recalculates_sale_and_credit_and_allows_payment(): void
    {
        [$sale, $credit] = $this->pendingCredit(0);
        $item = $sale->items()->firstOrFail();

        app(SaleService::class)->definePendingPrices($sale, [$item->id => '100']);
        $credit = $credit->fresh();

        $this->assertSame('100.00', $sale->fresh()->total);
        $this->assertSame('100.00', $credit->original_amount);
        $this->assertSame('100.00', $credit->balance);
        $this->assertFalse($credit->hasPendingPriceItems());

        app(CreditService::class)->registerPayment($credit, 100, PaymentMethod::Cash);
        $this->assertSame(CreditStatus::Paid, $credit->fresh()->status);
    }

    public function test_user_without_price_permission_cannot_define_prices(): void
    {
        [$sale] = $this->pendingCredit(100);
        $this->actingAs(User::where('email', 'cajera@construir.local')->firstOrFail());

        Livewire::test(SaleShow::class, ['sale' => $sale])
            ->call('openPendingPrices')
            ->assertForbidden();
    }

    public function test_cash_sale_cannot_use_pending_price(): void
    {
        [$product, $presentation, $location] = $this->saleData();

        Livewire::test(SaleForm::class)
            ->set('locationId', (string) $location->id)
            ->set('paymentType', 'cash')
            ->set('items', [[
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'quantity' => '1',
                'unit_price' => '',
                'subtotal' => '0',
                'price_pending' => true,
            ]])
            ->call('save')
            ->assertHasErrors('items.0.price_pending');
    }

    /** @return array{0: Product, 1: object, 2: Location, 3: Customer} */
    private function saleData(): array
    {
        $product = Product::query()->firstOrFail();
        $presentation = $product->presentations()->firstOrFail();
        $location = Location::query()->where('is_default', true)->firstOrFail();
        $customer = Customer::factory()->create(['credit_limit' => 999999]);

        return [$product, $presentation, $location, $customer];
    }

    /** @return array{0: Sale, 1: Credit} */
    private function pendingCredit(float $amount): array
    {
        [$product, $presentation, $location, $customer] = $this->saleData();
        $sale = Sale::create([
            'uuid' => (string) str()->uuid(),
            'code' => 'PENDING-'.str()->random(8),
            'customer_id' => $customer->id,
            'with_invoice' => false,
            'payment_type' => 'credit',
            'status' => 'completed',
            'subtotal' => $amount,
            'discount' => 0,
            'total' => $amount,
            'location_id' => $location->id,
            'created_by' => auth()->id(),
        ]);
        $sale->items()->create([
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'quantity' => 1,
            'base_quantity' => $presentation->equivalence,
            'unit_price' => 0,
            'subtotal' => 0,
            'price_pending' => true,
        ]);
        $credit = Credit::create([
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'original_amount' => $amount,
            'paid_amount' => 0,
            'balance' => $amount,
            'status' => CreditStatus::Open,
        ]);

        return [$sale->fresh(['items']), $credit];
    }
}
