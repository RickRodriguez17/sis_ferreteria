<?php

namespace App\Traits;

trait HasCreator
{
    protected static function bootHasCreator(): void
    {
        static::creating(function (object $model): void {
            if (auth()->check() && blank($model->created_by)) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function (object $model): void {
            if (auth()->check() && $model->isFillable('updated_by')) {
                $model->updated_by = auth()->id();
            }
        });
    }
}
