<?php

namespace App\Services;

use App\Domain\Enums\CreditStatus;
use App\Models\Credit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CreditAlertService
{
    public function daysAhead(): int
    {
        return max(1, (int) config('credit.alert_days', 7));
    }

    /** @return array{upcoming: Collection<int, Credit>, today: Collection<int, Credit>, overdue: Collection<int, Credit>} */
    public function buckets(): array
    {
        $today = today();
        $base = fn (): Builder => Credit::query()
            ->with(['customer', 'sale'])
            ->where('balance', '>', 0)
            ->where('status', '!=', CreditStatus::Cancelled)
            ->whereNotNull('due_date');

        return [
            'upcoming' => $base()->whereDate('due_date', '>', $today)->whereDate('due_date', '<=', $today->copy()->addDays($this->daysAhead()))->orderBy('due_date')->get(),
            'today' => $base()->whereDate('due_date', $today)->orderBy('due_date')->get(),
            'overdue' => $base()->whereDate('due_date', '<', $today)->orderBy('due_date')->get(),
        ];
    }

    /** @return array<string, mixed> */
    public function summary(): array
    {
        $buckets = $this->buckets();

        return [
            'creditAlerts' => $buckets,
            'upcomingCount' => $buckets['upcoming']->count(),
            'upcomingAmount' => (float) $buckets['upcoming']->sum('balance'),
            'dueTodayCount' => $buckets['today']->count(),
            'dueTodayAmount' => (float) $buckets['today']->sum('balance'),
            'overdueCount' => $buckets['overdue']->count(),
            'overdueAmount' => (float) $buckets['overdue']->sum('balance'),
            'delinquentCustomers' => $buckets['overdue']->groupBy('customer_id')->map(function (Collection $credits): array {
                return [
                    'customer' => $credits->first()->customer,
                    'credits' => $credits,
                    'balance' => (float) $credits->sum('balance'),
                    'days_overdue' => (int) $credits->max('days_overdue'),
                ];
            })->values(),
        ];
    }
}
