<?php

namespace App\Models;

use App\Domain\Enums\CashSessionStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['cash_register_id', 'opened_by', 'closed_by', 'opening_amount', 'closing_amount', 'counted_amount', 'difference', 'status', 'opened_at', 'closed_at'];

    protected function casts(): array
    {
        return ['opening_amount' => 'decimal:2', 'closing_amount' => 'decimal:2', 'counted_amount' => 'decimal:2', 'difference' => 'decimal:2', 'status' => CashSessionStatus::class, 'opened_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /** @return HasMany<CashMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function creditPayments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', CashSessionStatus::Open);
    }
}
