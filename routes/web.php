<?php

use App\Livewire\CatalogManager;
use App\Livewire\InventoryAdjust;
use App\Livewire\InventoryIndex;
use App\Livewire\InventoryMovements;
use App\Livewire\InventoryTransfer;
use App\Livewire\KardexIndex;
use App\Livewire\PriceHistoryIndex;
use App\Livewire\ProductForm;
use App\Livewire\ProductImport;
use App\Livewire\ProductIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware('auth')->group(function (): void {
    Route::get('products', ProductIndex::class)->name('products.index');
    Route::get('products/create', ProductForm::class)->name('products.create');
    Route::get('products/{product}/edit', ProductForm::class)->name('products.edit');
    Route::get('products/import', ProductImport::class)->name('products.import');
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
