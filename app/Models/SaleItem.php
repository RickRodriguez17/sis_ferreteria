<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|int $product_id
 * @property string|int|null $presentation_id
 * @property string|int $quantity
 * @property string|int $base_quantity
 */
class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = ['sale_id', 'product_id', 'presentation_id', 'quantity', 'base_quantity', 'unit_price', 'subtotal', 'price_pending'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4', 'base_quantity' => 'decimal:4', 'unit_price' => 'decimal:2', 'subtotal' => 'decimal:2', 'price_pending' => 'boolean'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Presentation, $this> */
    public function presentation(): BelongsTo
    {
        return $this->belongsTo(Presentation::class);
    }
}
