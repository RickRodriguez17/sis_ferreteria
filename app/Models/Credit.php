<?php

namespace App\Models;

use App\Domain\Enums\CreditStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Credit extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['customer_id', 'sale_id', 'original_amount', 'paid_amount', 'balance', 'status', 'due_date'];

    protected function casts(): array
    {
        return ['original_amount' => 'decimal:2', 'paid_amount' => 'decimal:2', 'balance' => 'decimal:2', 'status' => CreditStatus::class, 'due_date' => 'date'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return HasMany<CreditPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }

    protected function isOverdue(): Attribute
    {
        return Attribute::get(fn (): bool => $this->status !== CreditStatus::Cancelled
            && ($this->status === CreditStatus::Overdue
                || ($this->balance > 0 && $this->due_date instanceof Carbon && $this->due_date->isPast())));
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', CreditStatus::Cancelled)
            ->where(fn ($q) => $q->where('status', CreditStatus::Overdue)
                ->orWhere(fn ($q) => $q->where('balance', '>', 0)->whereDate('due_date', '<', now())));
    }
}
