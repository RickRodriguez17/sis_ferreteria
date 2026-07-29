<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasCreator;
use App\Traits\Immutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerReturn extends Model
{
    use Auditable, HasCreator, HasFactory, Immutable;

    protected $fillable = ['code', 'sale_id', 'customer_id', 'location_id', 'created_by', 'returned_at', 'total', 'notes'];

    protected function casts(): array
    {
        return ['returned_at' => 'datetime', 'total' => 'decimal:2'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
        return $this->hasMany(CustomerReturnItem::class);
    }
}
