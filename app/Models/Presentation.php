<?php

namespace App\Models;

use App\Domain\Enums\PriceField;
use App\Services\Support\UnitConverter;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property string|int $equivalence
 * @property string|int $price_without_invoice
 * @property string|int $price_with_invoice
 */
class Presentation extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'product_id', 'name', 'equivalence', 'price_without_invoice',
        'price_with_invoice', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'equivalence' => 'decimal:4',
            'price_without_invoice' => 'decimal:2',
            'price_with_invoice' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function priceHistories(): MorphMany
    {
        return $this->morphMany(PriceHistory::class, 'priceable');
    }

    public function baseQuantity(float|int|string $quantity): string
    {
        return app(UnitConverter::class)->toBase($quantity, $this->equivalence);
    }

    public function price(PriceField $field): string
    {
        if ($field === PriceField::Cost) {
            return (string) $this->product->cost;
        }

        return (string) $this->{$field->value};
    }
}
