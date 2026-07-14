<?php

namespace App\Models;

use App\Domain\Enums\CustomerType;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|int|null $credit_limit
 */
class Customer extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = ['type', 'name', 'document_type', 'document_number', 'phone', 'email', 'address', 'credit_limit', 'is_active'];

    protected function casts(): array
    {
        return ['type' => CustomerType::class, 'credit_limit' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /** @return HasMany<Credit, $this> */
    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
