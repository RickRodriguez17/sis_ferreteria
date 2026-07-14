<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasCreator;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use Auditable, HasCreator, HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid', 'code', 'barcode', 'name', 'description', 'category_id', 'brand_id',
        'unit_id', 'min_stock', 'cost', 'is_active', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'min_stock' => 'decimal:4',
            'cost' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<Presentation, $this> */
    public function presentations(): HasMany
    {
        return $this->hasMany(Presentation::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'product_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function receptionItems(): HasMany
    {
        return $this->hasMany(ReceptionItem::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_attribute_value');
    }

    /** Products intentionally related from this product's perspective. */
    public function related(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_related', 'product_id', 'related_product_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $like = '%'.mb_strtolower($term).'%';
            $query->whereRaw('LOWER(products.name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(products.code) LIKE ?', [$like])
                ->orWhereRaw('LOWER(products.barcode) LIKE ?', [$like])
                ->orWhereHas('brand', fn (Builder $brand) => $brand->whereRaw('LOWER(name) LIKE ?', [$like]))
                ->orWhereHas('attributeValues', fn (Builder $value) => $value->whereRaw('LOWER(value) LIKE ?', [$like]));
        });
    }

    public function stockTotal(): string
    {
        return (string) $this->inventories()->sum('quantity');
    }
}
