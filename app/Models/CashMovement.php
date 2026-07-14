<?php

namespace App\Models;

use App\Domain\Enums\CashMovementType;
use App\Domain\Enums\PaymentMethod;
use App\Traits\Immutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CashMovement extends Model
{
    use HasFactory, Immutable;

    protected $fillable = ['cash_session_id', 'type', 'method', 'amount', 'reference_type', 'reference_id', 'description', 'created_by'];

    protected function casts(): array
    {
        return ['type' => CashMovementType::class, 'method' => PaymentMethod::class, 'amount' => 'decimal:2'];
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
