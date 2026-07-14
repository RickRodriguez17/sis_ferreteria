<?php

namespace App\Traits;

use LogicException;

trait Immutable
{
    protected static function bootImmutable(): void
    {
        static::updating(function (): never {
            throw new LogicException('This record is immutable.');
        });

        static::deleting(function (): never {
            throw new LogicException('This record is immutable.');
        });
    }
}
