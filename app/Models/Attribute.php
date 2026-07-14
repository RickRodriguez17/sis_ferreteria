<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasFactory;

    protected $table = 'attributes';

    protected $fillable = ['name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
