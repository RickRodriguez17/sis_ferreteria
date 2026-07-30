<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasCreator;
use App\Traits\Immutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierReturn extends Model
{
    use Auditable, HasCreator, HasFactory, Immutable;

    protected $fillable = ['code', 'reception_id', 'purchase_id', 'supplier_id', 'location_id', 'created_by', 'returned_at', 'total', 'notes'];

    protected function casts(): array
    {
        return ['returned_at' => 'datetime', 'total' => 'decimal:2'];
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierReturnItem::class);
    }
}
