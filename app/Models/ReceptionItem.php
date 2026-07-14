<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|int $product_id
 * @property string|int $quantity
 * @property string|int $unit_cost
 */
class ReceptionItem extends Model
{
    use HasFactory;

    protected $fillable = ['reception_id', 'purchase_item_id', 'product_id', 'quantity', 'unit_cost'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4', 'unit_cost' => 'decimal:4'];
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    /** @return BelongsTo<PurchaseItem, $this> */
    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
