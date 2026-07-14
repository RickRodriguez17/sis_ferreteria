<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|int $product_id
 * @property string|int|null $presentation_id
 * @property string|int $quantity
 * @property string|int $unit_price
 * @property string|int $subtotal
 */
class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = ['quotation_id', 'product_id', 'presentation_id', 'quantity', 'unit_price', 'subtotal'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4', 'unit_price' => 'decimal:2', 'subtotal' => 'decimal:2'];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
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
