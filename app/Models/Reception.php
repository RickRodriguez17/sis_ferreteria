<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reception extends Model
{
    use Auditable, HasCreator, HasFactory;

    protected $fillable = ['code', 'purchase_id', 'location_id', 'received_at', 'notes', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['received_at' => 'datetime'];
    }

    /** @return BelongsTo<Purchase, $this> */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return HasMany<ReceptionItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ReceptionItem::class);
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
