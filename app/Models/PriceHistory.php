<?php

namespace App\Models;

use App\Domain\Enums\PriceField;
use App\Traits\Immutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PriceHistory extends Model
{
    use HasFactory, Immutable;

    protected $fillable = ['priceable_type', 'priceable_id', 'field', 'old_value', 'new_value', 'reason', 'changed_by'];

    protected function casts(): array
    {
        return ['field' => PriceField::class, 'old_value' => 'decimal:4', 'new_value' => 'decimal:4'];
    }

    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
