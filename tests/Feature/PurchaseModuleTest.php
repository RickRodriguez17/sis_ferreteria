<?php

namespace Tests\Feature;

use App\Livewire\PurchaseForm;
use App\Livewire\PurchaseShow;
use App\Livewire\ReceptionForm;
use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('email', 'admin@construir.local')->firstOrFail());
    }

    public function test_purchase_form_creates_purchase_without_changing_inventory(): void
    {
        $product = Product::query()->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();
        $stockBefore = (float) $product->stockTotal();

        Livewire::test(PurchaseForm::class)
            ->set('supplierId', $supplier->id)
            ->set('items', [['product_id' => $product->id, 'quantity_ordered' => '3', 'quantity_received' => '0', 'unit_cost' => '12', 'subtotal' => '36']])
            ->call('save')
            ->assertRedirect();

        $this->assertDatabaseHas('purchases', ['supplier_id' => $supplier->id, 'total' => 36]);
        $this->assertSame($stockBefore, (float) $product->fresh()->stockTotal());
    }

    public function test_partial_and_complete_receptions_update_stock_cost_history_and_status(): void
    {
        $product = Product::query()->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();
        $location = Location::query()->where('is_default', true)->firstOrFail();
        $purchase = app(PurchaseService::class)->create(['supplier_id' => $supplier->id, 'payment_type' => 'cash', 'total' => 48, 'items' => [['product_id' => $product->id, 'quantity_ordered' => 4, 'quantity_received' => 0, 'unit_cost' => 12, 'subtotal' => 48]]]);
        $stockBefore = (float) $product->fresh()->stockTotal();
        $movementBefore = StockMovement::count();

        Livewire::test(ReceptionForm::class, ['purchase' => $purchase])
            ->set('locationId', $location->id)
            ->set('items.'.$purchase->items->first()->id.'.quantity', '2')
            ->call('save')
            ->assertRedirect();

        $this->assertSame('partial', $purchase->fresh()->status->value);
        $this->assertSame($stockBefore + 2, (float) $product->fresh()->stockTotal());
        $this->assertSame($movementBefore + 1, StockMovement::count());
        $this->assertDatabaseHas('cost_histories', ['product_id' => $product->id, 'reception_id' => $purchase->fresh()->receptions->first()->id]);

        $purchase = $purchase->fresh();
        Livewire::test(ReceptionForm::class, ['purchase' => $purchase])
            ->set('locationId', $location->id)
            ->set('items.'.$purchase->items->first()->id.'.quantity', '2')
            ->call('save')
            ->assertRedirect();

        $this->assertSame('completed', $purchase->fresh()->status->value);
        $this->assertSame($stockBefore + 4, (float) $product->fresh()->stockTotal());
    }

    public function test_excess_reception_is_reported_without_stock_movement(): void
    {
        $product = Product::query()->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();
        $location = Location::query()->where('is_default', true)->firstOrFail();
        $purchase = app(PurchaseService::class)->create(['supplier_id' => $supplier->id, 'payment_type' => 'cash', 'total' => 12, 'items' => [['product_id' => $product->id, 'quantity_ordered' => 1, 'quantity_received' => 0, 'unit_cost' => 12, 'subtotal' => 12]]]);
        $before = StockMovement::count();

        Livewire::test(ReceptionForm::class, ['purchase' => $purchase])
            ->set('locationId', $location->id)
            ->set('items.'.$purchase->items->first()->id.'.quantity', '2')
            ->call('save')
            ->assertHasErrors('items');

        $this->assertSame($before, StockMovement::count());
        $this->assertSame('pending', $purchase->fresh()->status->value);
    }

    public function test_purchase_can_be_cancelled_only_without_receptions(): void
    {
        $product = Product::query()->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();
        $purchase = app(PurchaseService::class)->create(['supplier_id' => $supplier->id, 'payment_type' => 'cash', 'total' => 12, 'items' => [['product_id' => $product->id, 'quantity_ordered' => 1, 'quantity_received' => 0, 'unit_cost' => 12, 'subtotal' => 12]]]);
        Livewire::test(PurchaseShow::class, ['purchase' => $purchase])->call('cancel');
        $this->assertSame('cancelled', $purchase->fresh()->status->value);

        $received = Purchase::query()->where('status', 'completed')->firstOrFail();
        Livewire::test(PurchaseShow::class, ['purchase' => $received])->call('cancel')->assertHasErrors('cancel');
        $this->assertSame('completed', $received->fresh()->status->value);
    }

    public function test_cashier_cannot_open_purchase_creation(): void
    {
        $cashier = User::where('email', 'cajera@construir.local')->firstOrFail();
        $this->actingAs($cashier);
        $this->get(route('purchases.create'))->assertForbidden();
    }

    public function test_admin_can_apply_margin_suggested_price_and_records_history(): void
    {
        $purchase = Purchase::query()->where('status', 'completed')->with('items.product.presentations')->firstOrFail();
        $item = $purchase->items->first(fn ($item): bool => (float) $item->quantity_received > 0);
        $presentation = $item->product->presentations->firstOrFail();
        $suggested = bcmul((string) $item->product->cost, '1.30', 2);

        Livewire::test(PurchaseShow::class, ['purchase' => $purchase])
            ->set('priceReason', 'Margen estándar de reposición')
            ->call('applySuggestedPrice', $presentation->id, 'price_without_invoice', $item->product_id);

        $this->assertDatabaseHas('price_histories', [
            'priceable_type' => $presentation->getMorphClass(),
            'priceable_id' => $presentation->id,
            'field' => 'price_without_invoice',
            'new_value' => $suggested,
            'reason' => 'Margen estándar de reposición',
        ]);
        $this->assertSame($suggested, (string) $presentation->fresh()->price_without_invoice);
    }

    public function test_cashier_cannot_apply_margin_suggested_price(): void
    {
        $purchase = Purchase::query()->where('status', 'completed')->with('items.product.presentations')->firstOrFail();
        $item = $purchase->items->first(fn ($item): bool => (float) $item->quantity_received > 0);
        $presentation = $item->product->presentations->firstOrFail();
        $originalPrice = (string) $presentation->price_without_invoice;
        $cashier = User::where('email', 'cajera@construir.local')->firstOrFail();
        $this->actingAs($cashier);

        Livewire::test(PurchaseShow::class, ['purchase' => $purchase])
            ->assertForbidden();

        $this->assertSame($originalPrice, (string) $presentation->fresh()->price_without_invoice);
    }
}
