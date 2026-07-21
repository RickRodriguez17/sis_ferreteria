<?php

use App\Http\Controllers\DocumentPdfController;
use App\Http\Controllers\ReportPdfController;
use App\Livewire\CashRegisterPanel;
use App\Livewire\CashSessionIndex;
use App\Livewire\CashSessionShow;
use App\Livewire\CatalogManager;
use App\Livewire\CreditIndex;
use App\Livewire\CreditShow;
use App\Livewire\CustomerForm;
use App\Livewire\CustomerIndex;
use App\Livewire\CustomerShow;
use App\Livewire\Dashboard;
use App\Livewire\InventoryAdjust;
use App\Livewire\InventoryIndex;
use App\Livewire\InventoryMovements;
use App\Livewire\InventoryTransfer;
use App\Livewire\KardexIndex;
use App\Livewire\PaymentAccountIndex;
use App\Livewire\PriceHistoryIndex;
use App\Livewire\ProductForm;
use App\Livewire\ProductImport;
use App\Livewire\ProductIndex;
use App\Livewire\PurchaseForm;
use App\Livewire\PurchaseIndex;
use App\Livewire\PurchaseShow;
use App\Livewire\QuotationForm;
use App\Livewire\QuotationIndex;
use App\Livewire\QuotationShow;
use App\Livewire\ReceptionForm;
use App\Livewire\ReportIndex;
use App\Livewire\SaleForm;
use App\Livewire\SaleIndex;
use App\Livewire\SaleShow;
use App\Livewire\SupplierIndex;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('dashboard', Dashboard::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware('auth')->group(function (): void {
    Route::get('products', ProductIndex::class)->name('products.index');
    Route::get('products/create', ProductForm::class)->name('products.create');
    Route::get('products/{product}/edit', ProductForm::class)->name('products.edit');
    Route::get('products/import', ProductImport::class)->name('products.import');
    Route::get('suppliers', SupplierIndex::class)->name('suppliers.index');
    Route::get('customers', CustomerIndex::class)->name('customers.index');
    Route::get('customers/create', CustomerForm::class)->name('customers.create');
    Route::get('customers/{customer}/edit', CustomerForm::class)->name('customers.edit');
    Route::get('customers/{customer}', CustomerShow::class)->name('customers.show');
    Route::get('sales', SaleIndex::class)->name('sales.index');
    Route::get('sales/create', SaleForm::class)->name('sales.create');
    Route::get('sales/{sale}', SaleShow::class)->name('sales.show');
    Route::get('sales/{sale}/pdf', [DocumentPdfController::class, 'sale'])->name('sales.pdf');
    Route::get('quotations', QuotationIndex::class)->name('quotations.index');
    Route::get('quotations/create', QuotationForm::class)->name('quotations.create');
    Route::get('quotations/{quotation}/edit', QuotationForm::class)->name('quotations.edit');
    Route::get('quotations/{quotation}', QuotationShow::class)->name('quotations.show');
    Route::get('quotations/{quotation}/pdf', [DocumentPdfController::class, 'quotation'])->name('quotations.pdf');
    Route::get('credits', CreditIndex::class)->name('credits.index');
    Route::get('credits/{credit}', CreditShow::class)->name('credits.show');
    Route::get('cash', CashRegisterPanel::class)->name('cash.index');
    Route::get('cash/sessions', CashSessionIndex::class)->name('cash.sessions.index');
    Route::get('cash/sessions/{session}', CashSessionShow::class)->name('cash.sessions.show');
    Route::get('cash/payment-accounts', PaymentAccountIndex::class)->name('cash.payment-accounts.index');
    Route::get('reports/{type?}', ReportIndex::class)->name('reports.index');
    Route::get('reports/{type}/pdf', ReportPdfController::class)->name('reports.pdf');
    Route::get('purchases', PurchaseIndex::class)->name('purchases.index');
    Route::get('purchases/create', PurchaseForm::class)->name('purchases.create');
    Route::get('purchases/{purchase}/edit', PurchaseForm::class)->name('purchases.edit');
    Route::get('purchases/{purchase}', PurchaseShow::class)->name('purchases.show');
    Route::get('purchases/{purchase}/receptions/create', ReceptionForm::class)->name('receptions.create');
    Route::get('catalog/{type}', CatalogManager::class)->name('catalog.manager');
    Route::redirect('categories', 'catalog/categories')->name('catalog.categories');
    Route::redirect('brands', 'catalog/brands')->name('catalog.brands');
    Route::redirect('units', 'catalog/units')->name('catalog.units');
    Route::redirect('attributes', 'catalog/attributes')->name('catalog.attributes');
    Route::get('inventory', InventoryIndex::class)->name('inventory.index');
    Route::get('inventory/transfer', InventoryTransfer::class)->name('inventory.transfer');
    Route::get('inventory/adjust', InventoryAdjust::class)->name('inventory.adjust');
    Route::get('inventory/kardex', KardexIndex::class)->name('inventory.kardex');
    Route::get('inventory/movements', InventoryMovements::class)->name('inventory.movements');
    Route::get('inventory/prices', PriceHistoryIndex::class)->name('inventory.prices');
});

require __DIR__.'/auth.php';
