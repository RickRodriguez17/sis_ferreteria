<?php

namespace App\Livewire;

use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Unit;
use App\Services\PriceService;
use App\Services\ProductService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductForm extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $name = '';

    public string $code = '';

    public ?string $barcode = null;

    public ?string $description = null;

    public ?int $categoryId = null;

    public ?int $brandId = null;

    public ?int $unitId = null;

    public string $minStock = '0';

    public string $cost = '0';

    public bool $isActive = true;

    public array $presentations = [];

    public array $attributeValueIds = [];

    public array $relatedProductIds = [];

    public array $images = [];

    public array $existingImages = [];

    public function mount(?Product $product = null): void
    {
        $this->product = $product?->exists ? $product->load(['presentations', 'images', 'attributeValues', 'related']) : null;
        Gate::authorize($this->product ? 'update' : 'create', $this->product ?: Product::class);
        if ($this->product) {
            $this->fill(['name' => $this->product->name, 'code' => $this->product->code, 'barcode' => $this->product->barcode, 'description' => $this->product->description, 'categoryId' => $this->product->category_id, 'brandId' => $this->product->brand_id, 'unitId' => $this->product->unit_id, 'minStock' => (string) $this->product->min_stock, 'cost' => (string) $this->product->cost, 'isActive' => $this->product->is_active]);
            $this->presentations = $this->product->presentations->map(fn ($presentation): array => ['id' => $presentation->id, 'name' => $presentation->name, 'equivalence' => (string) $presentation->equivalence, 'price_without_invoice' => (string) $presentation->price_without_invoice, 'price_with_invoice' => (string) $presentation->price_with_invoice, 'is_active' => $presentation->is_active, 'sort_order' => $presentation->sort_order])->all();
            $this->attributeValueIds = $this->product->attributeValues->pluck('id')->all();
            $this->relatedProductIds = $this->product->related->pluck('id')->all();
            $this->existingImages = $this->product->images->toArray();
        } else {
            $this->addPresentation();
        }
    }

    public function addPresentation(): void
    {
        $this->presentations[] = ['name' => '', 'equivalence' => '1', 'price_without_invoice' => '0', 'price_with_invoice' => '0', 'is_active' => true, 'sort_order' => count($this->presentations)];
    }

    public function removePresentation(int $index): void
    {
        unset($this->presentations[$index]);
        $this->presentations = array_values($this->presentations);
    }

    public function removeImage(int $index): void
    {
        $image = $this->existingImages[$index] ?? null;
        if ($image && $this->product) {
            Storage::disk($image['disk'])->delete($image['path']);
            $this->product->images()->whereKey($image['id'])->delete();
        }
        unset($this->existingImages[$index]);
        $this->existingImages = array_values($this->existingImages);
    }

    public function save(ProductService $service, PriceService $priceService): void
    {
        $this->validate(['name' => ['required', 'string', 'max:255'], 'code' => ['nullable', 'string', 'max:100', Rule::unique('products', 'code')->ignore($this->product?->id)], 'barcode' => ['nullable', 'string', Rule::unique('products', 'barcode')->ignore($this->product?->id)], 'categoryId' => ['required', 'exists:categories,id'], 'brandId' => ['required', 'exists:brands,id'], 'unitId' => ['required', 'exists:units,id'], 'minStock' => ['nullable', 'numeric', 'decimal:0,4'], 'cost' => ['nullable', 'numeric', 'decimal:0,4'], 'presentations' => ['required', 'array', 'min:1'], 'presentations.*.name' => ['required', 'string'], 'presentations.*.equivalence' => ['required', 'numeric', 'gt:0'], 'presentations.*.price_without_invoice' => ['required', 'numeric', 'min:0'], 'presentations.*.price_with_invoice' => ['required', 'numeric', 'min:0'], 'images.*' => ['image', 'max:5120']]);
        $paths = collect($this->existingImages)->map(fn (array $image): array => ['path' => $image['path'], 'disk' => $image['disk'], 'is_primary' => $image['is_primary'], 'sort_order' => $image['sort_order']])->all();
        foreach ($this->images as $index => $image) {
            $paths[] = ['path' => $image->store('products', 'public'), 'disk' => 'public', 'is_primary' => count($paths) === 0, 'sort_order' => count($paths) + $index];
        }
        $data = ['name' => $this->name, 'code' => $this->code ?: null, 'barcode' => $this->barcode ?: null, 'description' => $this->description, 'category_id' => $this->categoryId, 'brand_id' => $this->brandId, 'unit_id' => $this->unitId, 'min_stock' => $this->minStock, 'cost' => $this->cost, 'is_active' => $this->isActive, 'attribute_value_ids' => $this->attributeValueIds, 'related_product_ids' => $this->relatedProductIds, 'presentations' => $this->presentations, 'images' => $paths];
        $priceChanges = [];
        if ($this->product) {
            $original = $this->product->presentations->keyBy('id');
            foreach ($this->presentations as $presentation) {
                if (! empty($presentation['id']) && $original->has($presentation['id'])) {
                    foreach (['price_without_invoice', 'price_with_invoice'] as $field) {
                        if ((string) $original[$presentation['id']]->{$field} !== (string) $presentation[$field]) {
                            $priceChanges[] = [$presentation['id'], $field, $presentation[$field]];
                        }
                    }
                }
            }
        }
        $saved = $this->product ? $service->update($this->product, $data, true) : $service->create($data);
        foreach ($priceChanges as [$presentationId, $field, $value]) {
            $priceService->changePrice(Presentation::findOrFail($presentationId), $field, $value, 'Actualización desde ficha de producto');
        }
        session()->flash('success', 'Producto guardado correctamente.');
        $this->redirectRoute('products.edit', $saved, navigate: true);
    }

    public function render()
    {
        return view('livewire.product-form', ['categories' => Category::query()->orderBy('name')->get(['id', 'name']), 'brands' => Brand::query()->orderBy('name')->get(['id', 'name']), 'units' => Unit::query()->active()->orderBy('name')->get(['id', 'name']), 'attributeValues' => AttributeValue::query()->with('attribute:id,name')->orderBy('value')->get(['id', 'attribute_id', 'value']), 'relatedProducts' => Product::query()->when($this->product, fn ($query) => $query->where('id', '!=', $this->product->id))->active()->orderBy('name')->get(['id', 'name', 'code'])])->layout('layouts.app');
    }
}
