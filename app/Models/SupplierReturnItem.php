<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierReturnItem extends Model
{
    use HasFactory;

    protected $fillable = ['supplier_return_id', 'reception_item_id', 'product_id', 'quantity_base', 'unit_cost', 'subtotal'];

    protected function casts(): array
    {
        return ['quantity_base' => 'decimal:4', 'unit_cost' => 'decimal:4', 'subtotal' => 'decimal:2'];
    }

    public function supplierReturn(): BelongsTo
    {
        return $this->belongsTo(SupplierReturn::class);
    }

    public function receptionItem(): BelongsTo
    {
        return $this->belongsTo(ReceptionItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
