<?php

namespace Database\Seeders;

use App\Domain\Enums\PaymentType;
use App\Models\Brand;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\Reception;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\ReceptionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FerreteriaSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@construir.local')->firstOrFail();
        Auth::setUser($admin);

        $units = $this->seedUnits();
        $categories = $this->seedCategories();
        $brands = $this->seedBrands();

        $products = collect($this->catalog())->map(function (array $row) use ($units, $categories, $brands): array {
            $product = Product::updateOrCreate(['code' => $row['code']], [
                'name' => $row['name'],
                'description' => $row['name'],
                'category_id' => $categories[$row['category']]->id,
                'brand_id' => $brands[$row['brand']]->id,
                'unit_id' => $units[$row['unit']]->id,
                'min_stock' => $row['min_stock'],
                'cost' => $row['cost'],
                'is_active' => true,
            ]);
            $product->presentations()->updateOrCreate(['name' => $row['presentation']], [
                'equivalence' => 1,
                'price_without_invoice' => $row['price_without_invoice'],
                'price_with_invoice' => $row['price_with_invoice'],
                'is_active' => true,
            ]);

            return ['product' => $product->fresh('presentations'), 'stock' => $row['stock'], 'cost' => $row['cost']];
        });

        $this->seedCommercialParties();
        $this->seedInitialStock($products);
    }

    /**
     * @return array<string, Unit>
     */
    private function seedUnits(): array
    {
        $units = [
            'und' => 'Unidad', 'm' => 'Metro', 'kg' => 'Kilogramo', 'caja' => 'Caja',
            'rollo' => 'Rollo', 'bolsa' => 'Bolsa', 'barra' => 'Barra', 'l' => 'Litro',
            'gal' => 'Galón', 'saco' => 'Saco',
        ];
        $result = [];
        foreach ($units as $abbreviation => $name) {
            $result[$abbreviation] = Unit::updateOrCreate(['abbreviation' => $abbreviation], ['name' => $name, 'is_active' => true]);
        }

        return $result;
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $categories = [
            'cemento' => 'Cemento y Agregados',
            'fierro' => 'Fierro y Construcción',
            'fijaciones' => 'Fijaciones',
            'plomeria' => 'Plomería y PVC',
            'pinturas' => 'Pinturas y Acabados',
            'herr-manuales' => 'Herramientas Manuales',
            'herr-electricas' => 'Herramientas Eléctricas',
            'electricidad' => 'Material Eléctrico',
            'adhesivos' => 'Adhesivos y Selladores',
            'seguridad' => 'Seguridad Industrial',
        ];
        $result = [];
        foreach ($categories as $key => $name) {
            $result[$key] = Category::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'is_active' => true]);
        }

        return $result;
    }

    /**
     * @return array<string, Brand>
     */
    private function seedBrands(): array
    {
        $result = [];
        foreach (['SOBOCE', 'COBOCE', 'Truper', 'Stanley', 'Bosch', 'DeWalt', 'Sika', 'Monopol', 'Tigre', 'Fuller', '3M', 'Genérico'] as $name) {
            $result[$name] = Brand::firstOrCreate(['name' => $name], ['is_active' => true]);
        }

        return $result;
    }

    private function seedCommercialParties(): void
    {
        $customers = [
            ['type' => 'occasional', 'name' => 'Consumidor Final', 'document_type' => 'CI', 'document_number' => '0', 'is_active' => true],
            ['type' => 'registered', 'name' => 'Constructora Andina S.R.L.', 'document_type' => 'NIT', 'document_number' => '1023456789', 'phone' => '76512345', 'email' => 'ventas@andina.bo', 'address' => 'Av. Blanco Galindo Km 4, Cochabamba', 'credit_limit' => 20000, 'is_active' => true],
            ['type' => 'registered', 'name' => 'Juan Pérez Mamani', 'document_type' => 'CI', 'document_number' => '8765432', 'phone' => '70123456', 'address' => 'Calle Murillo 123, La Paz', 'credit_limit' => 5000, 'is_active' => true],
            ['type' => 'registered', 'name' => 'María Gutiérrez Rojas', 'document_type' => 'CI', 'document_number' => '5432198', 'phone' => '68899001', 'address' => 'Zona Sur, El Alto', 'credit_limit' => 3000, 'is_active' => true],
        ];
        foreach ($customers as $customer) {
            Customer::updateOrCreate(
                ['document_type' => $customer['document_type'], 'document_number' => $customer['document_number']],
                $customer
            );
        }

        $suppliers = [
            ['name' => 'Distribuidora SOBOCE S.A.', 'document_type' => 'NIT', 'document_number' => '1002003004', 'phone' => '2777888', 'email' => 'ventas@soboce.bo', 'address' => 'El Alto, La Paz', 'is_active' => true],
            ['name' => 'Importadora Truper Bolivia S.R.L.', 'document_type' => 'NIT', 'document_number' => '2003004005', 'phone' => '4455666', 'email' => 'contacto@truper.bo', 'address' => 'Santa Cruz de la Sierra', 'is_active' => true],
            ['name' => 'Ferretería Mayorista La Paz', 'document_type' => 'NIT', 'document_number' => '3004005006', 'phone' => '2333444', 'address' => 'Av. Buenos Aires, La Paz', 'is_active' => true],
        ];
        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(['document_number' => $supplier['document_number']], $supplier);
        }

        PaymentAccount::firstOrCreate(['name' => 'QR Banco Unión'], ['type' => 'qr', 'details' => 'Cuenta QR empresarial', 'is_active' => true]);
        PaymentAccount::firstOrCreate(['name' => 'Transferencia BNB'], ['type' => 'transfer', 'details' => 'Cta. Cte. 1000123456', 'is_active' => true]);
        CashRegister::firstOrCreate(['name' => 'Caja Principal'], ['is_active' => true]);
    }

    /**
     * @param  Collection<int, array{product: Product, stock: int, cost: float}>  $products
     */
    private function seedInitialStock($products): void
    {
        if (Reception::where('code', 'REC-INIC-001')->exists()) {
            return;
        }

        $supplier = Supplier::where('name', 'Ferretería Mayorista La Paz')->firstOrFail();
        $location = Location::where('is_default', true)->firstOrFail();

        $items = $products->map(fn (array $row): array => [
            'product_id' => $row['product']->id,
            'quantity_ordered' => $row['stock'],
            'quantity_received' => 0,
            'unit_cost' => $row['cost'],
            'subtotal' => $row['stock'] * $row['cost'],
        ])->all();

        $purchase = app(PurchaseService::class)->create([
            'supplier_id' => $supplier->id,
            'payment_type' => PaymentType::Cash,
            'total' => array_sum(array_column($items, 'subtotal')),
            'items' => $items,
        ]);

        $reception = Reception::create([
            'code' => 'REC-INIC-001',
            'purchase_id' => $purchase->id,
            'location_id' => $location->id,
            'received_at' => now(),
        ]);
        $reception->items()->createMany(
            $purchase->items->map(fn ($item): array => [
                'purchase_item_id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity_ordered,
                'unit_cost' => $item->unit_cost,
            ])->all()
        );
        app(ReceptionService::class)->post($reception);
    }

    /**
     * @return list<array{code:string,name:string,category:string,brand:string,unit:string,presentation:string,cost:float,price_without_invoice:float,price_with_invoice:float,min_stock:int,stock:int}>
     */
    private function catalog(): array
    {
        return [
            ['code' => 'CEM-001', 'name' => 'Cemento Portland IP-30 SOBOCE 50kg', 'category' => 'cemento', 'brand' => 'SOBOCE', 'unit' => 'bolsa', 'presentation' => 'Bolsa 50kg', 'cost' => 50, 'price_without_invoice' => 56, 'price_with_invoice' => 63, 'min_stock' => 20, 'stock' => 200],
            ['code' => 'CEM-002', 'name' => 'Cemento COBOCE IP-40 50kg', 'category' => 'cemento', 'brand' => 'COBOCE', 'unit' => 'bolsa', 'presentation' => 'Bolsa 50kg', 'cost' => 52, 'price_without_invoice' => 58, 'price_with_invoice' => 65, 'min_stock' => 20, 'stock' => 150],
            ['code' => 'CEM-003', 'name' => 'Cal hidratada 25kg', 'category' => 'cemento', 'brand' => 'Genérico', 'unit' => 'bolsa', 'presentation' => 'Bolsa 25kg', 'cost' => 22, 'price_without_invoice' => 28, 'price_with_invoice' => 32, 'min_stock' => 10, 'stock' => 80],
            ['code' => 'CEM-004', 'name' => 'Yeso de construcción 25kg', 'category' => 'cemento', 'brand' => 'Genérico', 'unit' => 'bolsa', 'presentation' => 'Bolsa 25kg', 'cost' => 30, 'price_without_invoice' => 38, 'price_with_invoice' => 43, 'min_stock' => 10, 'stock' => 60],
            ['code' => 'FIE-001', 'name' => 'Fierro corrugado 6mm x 12m', 'category' => 'fierro', 'brand' => 'Genérico', 'unit' => 'barra', 'presentation' => 'Barra 12m', 'cost' => 22, 'price_without_invoice' => 28, 'price_with_invoice' => 32, 'min_stock' => 30, 'stock' => 300],
            ['code' => 'FIE-002', 'name' => 'Fierro corrugado 8mm x 12m', 'category' => 'fierro', 'brand' => 'Genérico', 'unit' => 'barra', 'presentation' => 'Barra 12m', 'cost' => 32, 'price_without_invoice' => 40, 'price_with_invoice' => 45, 'min_stock' => 30, 'stock' => 250],
            ['code' => 'FIE-003', 'name' => 'Fierro corrugado 10mm x 12m', 'category' => 'fierro', 'brand' => 'Genérico', 'unit' => 'barra', 'presentation' => 'Barra 12m', 'cost' => 50, 'price_without_invoice' => 62, 'price_with_invoice' => 70, 'min_stock' => 20, 'stock' => 200],
            ['code' => 'FIE-004', 'name' => 'Fierro corrugado 12mm x 12m', 'category' => 'fierro', 'brand' => 'Genérico', 'unit' => 'barra', 'presentation' => 'Barra 12m', 'cost' => 72, 'price_without_invoice' => 88, 'price_with_invoice' => 99, 'min_stock' => 15, 'stock' => 120],
            ['code' => 'FIE-005', 'name' => 'Alambre de amarre negro', 'category' => 'fierro', 'brand' => 'Genérico', 'unit' => 'kg', 'presentation' => 'Kilogramo', 'cost' => 11, 'price_without_invoice' => 15, 'price_with_invoice' => 17, 'min_stock' => 20, 'stock' => 150],
            ['code' => 'FIJ-001', 'name' => 'Clavo de construcción 2"', 'category' => 'fijaciones', 'brand' => 'Genérico', 'unit' => 'kg', 'presentation' => 'Kilogramo', 'cost' => 11, 'price_without_invoice' => 15, 'price_with_invoice' => 17, 'min_stock' => 15, 'stock' => 100],
            ['code' => 'FIJ-002', 'name' => 'Clavo de construcción 3"', 'category' => 'fijaciones', 'brand' => 'Genérico', 'unit' => 'kg', 'presentation' => 'Kilogramo', 'cost' => 11, 'price_without_invoice' => 15, 'price_with_invoice' => 17, 'min_stock' => 15, 'stock' => 100],
            ['code' => 'FIJ-003', 'name' => 'Clavo de construcción 4"', 'category' => 'fijaciones', 'brand' => 'Genérico', 'unit' => 'kg', 'presentation' => 'Kilogramo', 'cost' => 11, 'price_without_invoice' => 15, 'price_with_invoice' => 17, 'min_stock' => 10, 'stock' => 80],
            ['code' => 'FIJ-004', 'name' => 'Tornillo autorroscante 1" (caja 100)', 'category' => 'fijaciones', 'brand' => 'Truper', 'unit' => 'caja', 'presentation' => 'Caja 100u', 'cost' => 18, 'price_without_invoice' => 25, 'price_with_invoice' => 28, 'min_stock' => 10, 'stock' => 40],
            ['code' => 'FIJ-005', 'name' => 'Tarugo plástico 8mm (bolsa 100)', 'category' => 'fijaciones', 'brand' => 'Genérico', 'unit' => 'bolsa', 'presentation' => 'Bolsa 100u', 'cost' => 8, 'price_without_invoice' => 12, 'price_with_invoice' => 14, 'min_stock' => 10, 'stock' => 50],
            ['code' => 'PLO-001', 'name' => 'Tubo PVC agua 1/2" x 6m', 'category' => 'plomeria', 'brand' => 'Tigre', 'unit' => 'und', 'presentation' => 'Tubo 6m', 'cost' => 16, 'price_without_invoice' => 22, 'price_with_invoice' => 25, 'min_stock' => 20, 'stock' => 120],
            ['code' => 'PLO-002', 'name' => 'Tubo PVC desagüe 4" x 6m', 'category' => 'plomeria', 'brand' => 'Tigre', 'unit' => 'und', 'presentation' => 'Tubo 6m', 'cost' => 68, 'price_without_invoice' => 85, 'price_with_invoice' => 96, 'min_stock' => 10, 'stock' => 60],
            ['code' => 'PLO-003', 'name' => 'Codo PVC 1/2" x 90°', 'category' => 'plomeria', 'brand' => 'Tigre', 'unit' => 'und', 'presentation' => 'Unidad', 'cost' => 1.5, 'price_without_invoice' => 2.5, 'price_with_invoice' => 3, 'min_stock' => 50, 'stock' => 400],
            ['code' => 'PLO-004', 'name' => 'Llave de paso 1/2"', 'category' => 'plomeria', 'brand' => 'Genérico', 'unit' => 'und', 'presentation' => 'Unidad', 'cost' => 22, 'price_without_invoice' => 32, 'price_with_invoice' => 36, 'min_stock' => 10, 'stock' => 40],
            ['code' => 'PLO-005', 'name' => 'Pegamento para PVC 250ml', 'category' => 'plomeria', 'brand' => 'Genérico', 'unit' => 'und', 'presentation' => 'Frasco 250ml', 'cost' => 20, 'price_without_invoice' => 28, 'price_with_invoice' => 32, 'min_stock' => 10, 'stock' => 50],
            ['code' => 'PIN-001', 'name' => 'Pintura látex interior blanco (galón)', 'category' => 'pinturas', 'brand' => 'Monopol', 'unit' => 'gal', 'presentation' => 'Galón', 'cost' => 78, 'price_without_invoice' => 95, 'price_with_invoice' => 107, 'min_stock' => 8, 'stock' => 60],
            ['code' => 'PIN-002', 'name' => 'Pintura anticorrosiva negra 1/4 gal', 'category' => 'pinturas', 'brand' => 'Monopol', 'unit' => 'und', 'presentation' => '1/4 Galón', 'cost' => 28, 'price_without_invoice' => 38, 'price_with_invoice' => 43, 'min_stock' => 8, 'stock' => 40],
            ['code' => 'PIN-003', 'name' => 'Thinner corriente', 'category' => 'pinturas', 'brand' => 'Genérico', 'unit' => 'l', 'presentation' => 'Litro', 'cost' => 12, 'price_without_invoice' => 18, 'price_with_invoice' => 20, 'min_stock' => 10, 'stock' => 80],
            ['code' => 'PIN-004', 'name' => 'Rodillo de lana 9"', 'category' => 'pinturas', 'brand' => 'Truper', 'unit' => 'und', 'presentation' => 'Unidad', 'cost' => 14, 'price_without_invoice' => 22, 'price_with_invoice' => 25, 'min_stock' => 10, 'stock' => 50],
            ['code' => 'PIN-005', 'name' => 'Brocha 3"', 'category' => 'pinturas', 'brand' => 'Truper', 'unit' => 'und', 'presentation' => 'Unidad', 'cost' => 9, 'price_without_invoice' => 15, 'price_with_invoice' => 17, 'min_stock' => 15, 'stock' => 70],
            ['code' => 'HMA-001', 'name' => 'Martillo carpintero 16oz', 'category' => 'herr-manuales', 'brand' => 'Stanley', 'unit' => 'und', 'presentation' => 'Unidad', 'cost' => 45, 'price_without_invoice' => 65, 'price_with_invoice' => 73, 'min_stock' => 5, 'stock' => 30],
            ['code' => 'HMA-002', 'name' => 'Flexómetro 5m', 'category' => 'herr-manuales', 'brand' => 'Truper', 'unit' => 'und', 'presentation' => 'Unidad', 'cost' => 18, 'price_without_invoice' => 28, 'price_with_invoice' => 32, 'min_stock' => 8, 'stock' => 40],
            ['code' => 'HMA-003', 'name' => 'Alicate universal 8"', 'category' => 'herr-manuales', 'brand' => 'Truper', 'unit' => 'und', 'presentation' => 'Unidad', 'cost' => 32, 'price_without_invoice' => 48, 'price_with_invoice' => 54, 'min_stock' => 5, 'stock' => 25],
            ['code' => 'HMA-004', 'name' => 'Juego de destornilladores (6 pzas)', 'category' => 'herr-manuales', 'brand' => 'Stanley', 'unit' => 'caja', 'presentation' => 'Juego', 'cost' => 40, 'price_without_invoice' => 62, 'price_with_invoice' => 70, 'min_stock' => 5, 'stock' => 20],
            ['code' => 'HMA-005', 'name' => 'Serrucho 20"', 'category' => 'herr-manuales', 'brand' => 'Truper', 'unit' => 'und', 'presentation' => 'Unidad', 'cost' => 38, 'price_without_invoice' => 55, 'price_with_invoice' => 62, 'min_stock' => 5, 'stock' => 20],
            ['code' => 'HEL-001', 'name' => 'Taladro percutor 1/2" 650W', 'category' => 'herr-electricas', 'brand' => 'Bosch', 'unit' => 'und', 'presentation' => 'Unidad', 'cost' => 480, 'price_without_invoice' => 620, 'price_with_invoice' => 700, 'min_stock' => 3, 'stock' => 12],
            ['code' => 'HEL-002', 'name' => 'Amoladora angular 4-1/2" 850W', 'category' => 'herr-electricas', 'brand' => 'DeWalt', 'unit' => 'und', 'presentation' => 'Unidad', 'cost' => 520, 'price_without_invoice' => 680, 'price_with_invoice' => 768, 'min_stock' => 3, 'stock' => 10],
            ['code' => 'HEL-003', 'name' => 'Disco de corte metal 7"', 'category' => 'herr-electricas', 'brand' => 'Bosch', 'unit' => 'und', 'presentation' => 'Unidad', 'cost' => 8, 'price_without_invoice' => 12, 'price_with_invoice' => 14, 'min_stock' => 20, 'stock' => 120],
            ['code' => 'ELE-001', 'name' => 'Cable eléctrico #12 (rollo 100m)', 'category' => 'electricidad', 'brand' => 'Genérico', 'unit' => 'rollo', 'presentation' => 'Rollo 100m', 'cost' => 260, 'price_without_invoice' => 320, 'price_with_invoice' => 362, 'min_stock' => 5, 'stock' => 20],
            ['code' => 'ELE-002', 'name' => 'Foco LED 9W luz blanca', 'category' => 'electricidad', 'brand' => 'Genérico', 'unit' => 'und', 'presentation' => 'Unidad', 'cost' => 8, 'price_without_invoice' => 12, 'price_with_invoice' => 14, 'min_stock' => 20, 'stock' => 150],
            ['code' => 'ELE-003', 'name' => 'Cinta aislante 3/4"', 'category' => 'electricidad', 'brand' => '3M', 'unit' => 'und', 'presentation' => 'Unidad', 'cost' => 5, 'price_without_invoice' => 8, 'price_with_invoice' => 9, 'min_stock' => 20, 'stock' => 100],
            ['code' => 'ADH-001', 'name' => 'Silicona sellador 300ml', 'category' => 'adhesivos', 'brand' => 'Sika', 'unit' => 'und', 'presentation' => 'Cartucho 300ml', 'cost' => 20, 'price_without_invoice' => 30, 'price_with_invoice' => 34, 'min_stock' => 10, 'stock' => 60],
            ['code' => 'ADH-002', 'name' => 'Cola de carpintero 250g', 'category' => 'adhesivos', 'brand' => 'Fuller', 'unit' => 'und', 'presentation' => 'Frasco 250g', 'cost' => 10, 'price_without_invoice' => 16, 'price_with_invoice' => 18, 'min_stock' => 10, 'stock' => 50],
            ['code' => 'SEG-001', 'name' => 'Guantes de trabajo (par)', 'category' => 'seguridad', 'brand' => 'Truper', 'unit' => 'und', 'presentation' => 'Par', 'cost' => 8, 'price_without_invoice' => 14, 'price_with_invoice' => 16, 'min_stock' => 15, 'stock' => 80],
            ['code' => 'SEG-002', 'name' => 'Casco de seguridad', 'category' => 'seguridad', 'brand' => 'Truper', 'unit' => 'und', 'presentation' => 'Unidad', 'cost' => 22, 'price_without_invoice' => 35, 'price_with_invoice' => 40, 'min_stock' => 10, 'stock' => 40],
            ['code' => 'SEG-003', 'name' => 'Lentes de seguridad', 'category' => 'seguridad', 'brand' => '3M', 'unit' => 'und', 'presentation' => 'Unidad', 'cost' => 10, 'price_without_invoice' => 18, 'price_with_invoice' => 20, 'min_stock' => 15, 'stock' => 60],
        ];
    }
}
