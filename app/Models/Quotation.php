<?php

namespace App\Models;

use App\Domain\Enums\QuotationStatus;
use App\Traits\Auditable;
use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quotation extends Model
{
    use Auditable, HasCreator, HasFactory;

    protected $fillable = ['code', 'customer_id', 'with_invoice', 'status', 'valid_until', 'subtotal', 'total', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['with_invoice' => 'boolean', 'status' => QuotationStatus::class, 'valid_until' => 'date', 'subtotal' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<QuotationItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', QuotationStatus::Open);
    }
}
