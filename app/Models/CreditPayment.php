<?php

namespace App\Models;

use App\Domain\Enums\PaymentMethod;
use App\Traits\Auditable;
use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditPayment extends Model
{
    use Auditable, HasCreator, HasFactory;

    protected $fillable = ['credit_id', 'amount', 'method', 'cash_session_id', 'paid_at', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'method' => PaymentMethod::class, 'paid_at' => 'datetime'];
    }

    public function credit(): BelongsTo
    {
        return $this->belongsTo(Credit::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
