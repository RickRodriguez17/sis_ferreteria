<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|int $quantity_ordered
 * @property string|int $quantity_received
 * @property string|int $unit_cost
 * @property string|int $product_id
 */
class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = ['purchase_id', 'product_id', 'quantity_ordered', 'quantity_received', 'unit_cost', 'subtotal'];

    protected function casts(): array
    {
        return ['quantity_ordered' => 'decimal:4', 'quantity_received' => 'decimal:4', 'unit_cost' => 'decimal:4', 'subtotal' => 'decimal:2'];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function receptionItems(): HasMany
    {
        return $this->hasMany(ReceptionItem::class);
    }
}
