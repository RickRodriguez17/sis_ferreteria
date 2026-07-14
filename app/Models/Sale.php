<?php

namespace App\Models;

use App\Domain\Enums\PaymentType;
use App\Domain\Enums\SaleStatus;
use App\Traits\Auditable;
use App\Traits\HasCreator;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Sale extends Model
{
    use Auditable, HasCreator, HasFactory, HasUuid;

    protected $fillable = [
        'uuid', 'code', 'customer_id', 'quotation_id', 'with_invoice', 'payment_type',
        'status', 'subtotal', 'discount', 'total', 'location_id', 'cash_session_id',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'with_invoice' => 'boolean',
            'payment_type' => PaymentType::class,
            'status' => SaleStatus::class,
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** @return HasOne<Credit, $this> */
    public function credit(): HasOne
    {
        return $this->hasOne(Credit::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function cashMovements(): MorphMany
    {
        return $this->morphMany(CashMovement::class, 'reference');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
