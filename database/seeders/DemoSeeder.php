<?php

namespace Database\Seeders;

use App\Domain\Enums\PaymentMethod;
use App\Domain\Enums\PaymentType;
use App\Domain\Enums\SaleStatus;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\Reception;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\CashService;
use App\Services\CreditService;
use App\Services\PurchaseService;
use App\Services\ReceptionService;
use App\Services\SaleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@construir.local')->firstOrFail();
        Auth::setUser($admin);
        $unit = Unit::where('abbreviation', 'und')->firstOrFail();
        $brandNames = ['Truper', 'Stanley', 'Bosch', 'Sika', 'Pintuco'];
        $brands = collect($brandNames)->mapWithKeys(fn (string $name) => [$name => Brand::firstOrCreate(['name' => $name], ['is_active' => true])]);
        $category = Category::firstOrCreate(['slug' => 'herramientas'], ['name' => 'Herramientas', 'is_active' => true]);
        $attribute = Attribute::firstOrCreate(['name' => 'Medida'], ['is_active' => true]);
        $values = collect(['Pequeña', 'Mediana', 'Grande'])->map(fn (string $value) => AttributeValue::firstOrCreate(['attribute_id' => $attribute->id, 'value' => $value]));
        $products = collect(range(1, 18))->map(function (int $number) use ($brands, $category, $unit, $values): Product {
            $product = Product::firstOrCreate(['code' => 'DEMO-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT)], [
                'name' => 'Producto ferretero '.$number,
                'description' => 'Producto demo para ferretería',
                'category_id' => $category->id,
                'brand_id' => $brands->values()->get($number % $brands->count())->id,
                'unit_id' => $unit->id,
                'min_stock' => 2,
                'cost' => 0,
                'is_active' => true,
            ]);
            $product->presentations()->firstOrCreate(['name' => 'Unidad'], ['equivalence' => 1, 'price_without_invoice' => 15 + $number, 'price_with_invoice' => 17 + $number, 'is_active' => true]);
            $product->attributeValues()->syncWithoutDetaching([$values->get($number % $values->count())->id]);

            return $product->fresh('presentations');
        });

        $supplier = Supplier::firstOrCreate(['name' => 'Proveedor Demo'], ['document_number' => 'DEMO-SUP-001', 'is_active' => true]);
        $purchaseService = app(PurchaseService::class);
        $purchase = $purchaseService->create([
            'supplier_id' => $supplier->id,
            'payment_type' => PaymentType::Cash,
            'total' => 0,
            'items' => $products->take(10)->map(fn (Product $product): array => ['product_id' => $product->id, 'quantity_ordered' => 20, 'quantity_received' => 0, 'unit_cost' => 10, 'subtotal' => 200])->all(),
        ]);
        $location = Location::where('name', 'Patio')->firstOrFail();
        $reception = Reception::create(['code' => 'DEMO-REC-001', 'purchase_id' => $purchase->id, 'location_id' => $location->id, 'received_at' => now()]);
        $reception->items()->createMany($purchase->items->map(fn ($item): array => ['purchase_item_id' => $item->id, 'product_id' => $item->product_id, 'quantity' => 20, 'unit_cost' => 10])->all());
        app(ReceptionService::class)->post($reception);

        $registered = Customer::firstOrCreate(['email' => 'cliente@construir.local'], ['type' => 'registered', 'name' => 'Cliente Registrado', 'credit_limit' => 1000, 'is_active' => true]);
        Customer::firstOrCreate(['email' => 'ocasional@construir.local'], ['type' => 'occasional', 'name' => 'Cliente Ocasional', 'is_active' => true]);
        $register = CashRegister::firstOrCreate(['name' => 'Caja Principal'], ['is_active' => true]);
        $cash = app(CashService::class)->open($register, 500);
        $saleService = app(SaleService::class);

        $product = $products->first();
        $sale = Sale::create(['code' => 'DEMO-CASH-001', 'customer_id' => $registered->id, 'with_invoice' => false, 'payment_type' => PaymentType::Cash, 'status' => SaleStatus::Completed, 'subtotal' => 20, 'discount' => 0, 'total' => 20, 'location_id' => $location->id, 'cash_session_id' => $cash->id]);
        $sale->items()->create(['product_id' => $product->id, 'presentation_id' => $product->presentations->first()->id, 'quantity' => 1, 'base_quantity' => 0, 'unit_price' => 20, 'subtotal' => 20]);
        $saleService->confirm($sale);

        $creditProduct = $products->get(1);
        $creditSale = Sale::create(['code' => 'DEMO-CREDIT-001', 'customer_id' => $registered->id, 'with_invoice' => false, 'payment_type' => PaymentType::Credit, 'status' => SaleStatus::Completed, 'subtotal' => 25, 'discount' => 0, 'total' => 25, 'location_id' => $location->id]);
        $creditSale->items()->create(['product_id' => $creditProduct->id, 'presentation_id' => $creditProduct->presentations->first()->id, 'quantity' => 1, 'base_quantity' => 0, 'unit_price' => 25, 'subtotal' => 25]);
        $saleService->confirm($creditSale);
        app(CreditService::class)->registerPayment($creditSale->fresh('credit')->credit, 10, PaymentMethod::Cash, $cash);
    }
}
