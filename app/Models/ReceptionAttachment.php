<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceptionAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['reception_id', 'disk', 'path', 'original_name', 'mime_type', 'size'];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }
}
