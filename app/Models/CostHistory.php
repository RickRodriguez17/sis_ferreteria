<?php

namespace App\Models;

use App\Traits\Immutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostHistory extends Model
{
    use HasFactory, Immutable;

    public $timestamps = false;

    protected $fillable = ['product_id', 'reception_id', 'previous_cost', 'received_unit_cost', 'new_cost', 'created_by', 'created_at'];

    protected function casts(): array
    {
        return ['previous_cost' => 'decimal:4', 'received_unit_cost' => 'decimal:4', 'new_cost' => 'decimal:4', 'created_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
