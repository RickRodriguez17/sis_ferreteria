<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = ['product_id', 'location_id', 'quantity', 'reserved_quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4', 'reserved_quantity' => 'decimal:4'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function scopeBelowMinimum($query)
    {
        return $query->whereColumn('quantity', '<', 'products.min_stock')->join('products', 'products.id', '=', 'inventory.product_id');
    }
}
