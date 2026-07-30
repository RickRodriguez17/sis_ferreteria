<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerReturnItem extends Model
{
    use HasFactory;

    protected $fillable = ['customer_return_id', 'sale_item_id', 'product_id', 'presentation_id', 'quantity_base', 'unit_price', 'subtotal'];

    protected function casts(): array
    {
        return ['quantity_base' => 'decimal:4', 'unit_price' => 'decimal:4', 'subtotal' => 'decimal:2'];
    }

    public function customerReturn(): BelongsTo
    {
        return $this->belongsTo(CustomerReturn::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
