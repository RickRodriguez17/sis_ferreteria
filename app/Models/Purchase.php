<?php

namespace App\Models;

use App\Domain\Enums\PaymentType;
use App\Domain\Enums\PurchaseStatus;
use App\Traits\Auditable;
use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use Auditable, HasCreator, HasFactory;

    protected $fillable = [
        'code', 'supplier_id', 'status', 'payment_type', 'total',
        'expected_date', 'notes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseStatus::class,
            'payment_type' => PaymentType::class,
            'total' => 'decimal:2',
            'expected_date' => 'date',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return HasMany<PurchaseItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function receptions(): HasMany
    {
        return $this->hasMany(Reception::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [PurchaseStatus::Pending, PurchaseStatus::Partial]);
    }
}
