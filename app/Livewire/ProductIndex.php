<?php

namespace App\Livewire;

use App\Livewire\Traits\WithTableState;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ProductIndex extends Component
{
    use WithTableState;

    public string $status = '';

    public string $categoryId = '';

    public string $brandId = '';

    public string $locationId = '';

    public bool $belowMinimum = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Product::class);
    }

    public function delete(int $id): void
    {
        $product = Product::findOrFail($id);
        Gate::authorize('delete', $product);
        $product->delete();
        session()->flash('success', 'Producto eliminado.');
    }

    public function updated($property): void
    {
        if (in_array($property, ['status', 'categoryId', 'brandId', 'locationId', 'belowMinimum'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'unit:id,name,abbreviation', 'inventories:id,product_id,location_id,quantity'])
            ->search($this->search)
            ->when($this->status !== '', fn ($query) => $query->where('is_active', $this->status === 'active'))
            ->when($this->categoryId !== '', fn ($query) => $query->where('category_id', $this->categoryId))
            ->when($this->brandId !== '', fn ($query) => $query->where('brand_id', $this->brandId))
            ->when($this->locationId !== '', fn ($query) => $query->whereHas('inventories', fn ($inventory) => $inventory->where('location_id', $this->locationId)))
            ->when($this->belowMinimum, fn ($query) => $query->whereHas('inventories', fn ($inventory) => $inventory->whereColumn('quantity', '<', 'products.min_stock')))
            ->orderBy($this->sortBy === 'name' ? 'name' : $this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.product-index', ['products' => $products, 'categories' => Category::query()->orderBy('name')->get(['id', 'name']), 'brands' => Brand::query()->orderBy('name')->get(['id', 'name']), 'locations' => Location::query()->orderBy('name')->get(['id', 'name'])])->layout('layouts.app');
    }
}
