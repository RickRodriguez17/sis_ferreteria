<?php

namespace Tests\Feature;

use App\Exports\ProductTemplateExport;
use App\Livewire\CatalogManager;
use App\Livewire\ProductForm;
use App\Livewire\ProductImport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProductInventoryUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('email', 'admin@construir.local')->firstOrFail());
    }

    public function test_product_form_creates_related_product_and_image(): void
    {
        Storage::fake('public');
        $base = Product::query()->firstOrFail();
        $category = Category::query()->firstOrFail();
        $brand = Brand::query()->firstOrFail();
        $unit = Unit::query()->firstOrFail();

        Livewire::test(ProductForm::class)
            ->set('name', 'Producto UI')
            ->set('categoryId', $category->id)
            ->set('brandId', $brand->id)
            ->set('unitId', $unit->id)
            ->set('presentations', [['name' => 'Unidad', 'equivalence' => '1', 'price_without_invoice' => '20', 'price_with_invoice' => '22', 'is_active' => true, 'sort_order' => 0]])
            ->set('relatedProductIds', [$base->id])
            ->set('images', [UploadedFile::fake()->image('producto.jpg')])
            ->call('save')
            ->assertRedirect();

        $product = Product::where('name', 'Producto UI')->firstOrFail();
        $this->assertTrue($product->related->contains($base));
        $this->assertCount(1, $product->images);
        Storage::disk('public')->assertExists($product->images->first()->path);
    }

    public function test_catalog_creation_opens_and_closes_modal(): void
    {
        $component = Livewire::test(CatalogManager::class, ['type' => 'brands'])
            ->call('create')
            ->assertSet('showModal', true)
            ->assertDispatched('open-modal', 'catalog-record')
            ->set('name', 'Marca UI')
            ->call('save')
            ->assertSet('showModal', false)
            ->assertDispatched('close-modal', 'catalog-record');

        $this->assertDatabaseHas('brands', ['name' => 'Marca UI']);
        $this->assertNotNull($component);
    }

    public function test_product_import_processes_valid_rows_and_reports_invalid_rows(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'products-import-');
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['code', 'barcode', 'name', 'description', 'category', 'brand', 'unit', 'min_stock', 'cost', 'is_active', 'presentation_name', 'equivalence', 'price_without_invoice', 'price_with_invoice'],
            ['', 'IMPORT-001', 'Importado válido', 'Demo', 'Herramientas', 'Truper', 'und', 2, 10, 1, 'Unidad', 1, 15, 17],
            ['', 'IMPORT-002', '', '', '', '', '', '', '', 1, 'Unidad', 1, 15, 17],
        ]);
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        $contents = file_get_contents($path);
        unlink($path);

        $component = Livewire::test(ProductImport::class)
            ->set('file', UploadedFile::fake()->createWithContent('productos.xlsx', $contents))
            ->call('import')
            ->assertSet('result.processed', 1)
            ->assertSet('result.created', 1);

        $this->assertDatabaseHas('products', ['barcode' => 'IMPORT-001', 'name' => 'Importado válido']);
        $this->assertCount(1, $component->get('result')['errors']);
    }

    public function test_product_template_download_contains_expected_xlsx_columns(): void
    {
        $response = (new ProductTemplateExport)->download('plantilla-productos.xlsx');

        ob_start();
        $response->sendContent();
        $contents = ob_get_clean();

        $this->assertIsString($contents);
        $path = tempnam(sys_get_temp_dir(), 'products-template-');
        file_put_contents($path, $contents);
        $spreadsheet = IOFactory::load($path);
        $this->assertSame('code', $spreadsheet->getActiveSheet()->getCell('A1')->getValue());
        $this->assertSame('price_with_invoice', $spreadsheet->getActiveSheet()->getCell('N1')->getValue());
        $spreadsheet->disconnectWorksheets();
        unlink($path);
    }

    public function test_inventory_routes_require_authentication(): void
    {
        auth()->logout();
        $this->get(route('products.index'))->assertRedirect(route('login'));
        $this->get(route('inventory.index'))->assertRedirect(route('login'));
    }

    public function test_cashier_cannot_open_catalog_management_pages(): void
    {
        $cashier = User::factory()->create();
        $cashier->assignRole('Cajero');
        $this->actingAs($cashier);

        $this->get(route('catalog.manager', ['type' => 'brands']))->assertForbidden();
        $this->get(route('suppliers.index'))->assertForbidden();
    }

    public function test_authenticated_module_pages_render(): void
    {
        foreach ([
            'products.index',
            'catalog.manager',
            'inventory.index',
            'inventory.transfer',
            'inventory.adjust',
            'inventory.movements',
            'inventory.prices',
            'products.import',
        ] as $route) {
            $this->get(route($route, $route === 'catalog.manager' ? ['type' => 'categories'] : []))->assertOk();
        }
    }
}
