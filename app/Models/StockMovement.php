<?php

namespace App\Models;

use App\Domain\Enums\MovementDirection;
use App\Domain\Enums\StockMovementType;
use App\Traits\Immutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory, Immutable;

    protected $fillable = [
        'product_id', 'location_id', 'type', 'direction', 'quantity',
        'unit_cost', 'balance_after', 'reference_type', 'reference_id',
        'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'direction' => MovementDirection::class,
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'balance_after' => 'decimal:4',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
