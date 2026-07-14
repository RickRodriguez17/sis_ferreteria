<?php

namespace App\Traits;

use App\Models\Audit;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function (object $model): void {
            $model->writeAudit('created', [], $model->getAttributes());
        });

        static::updated(function (object $model): void {
            $changes = $model->getChanges();
            $old = [];
            foreach (array_keys($changes) as $key) {
                $old[$key] = $model->getOriginal($key);
            }
            $model->writeAudit('updated', $old, $changes);
        });

        static::deleted(function (object $model): void {
            $model->writeAudit('deleted', $model->getOriginal(), []);
        });
    }

    protected function writeAudit(string $event, array $oldValues, array $newValues): void
    {
        $excluded = array_merge(
            $this->getHidden(),
            property_exists($this, 'auditExclude') ? $this->auditExclude : [],
        );
        $excluded = array_fill_keys($excluded, true);

        Audit::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => $this->getMorphClass(),
            'auditable_id' => $this->getKey(),
            'old_values' => array_diff_key($oldValues, $excluded),
            'new_values' => array_diff_key($newValues, $excluded),
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            'url' => app()->runningInConsole() ? null : request()->fullUrl(),
        ]);
    }
}
