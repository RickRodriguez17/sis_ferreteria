<?php

namespace App\Repositories\Eloquent;

use App\Models\Credit;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Repositories\Contracts\ReportRepository;
use Illuminate\Support\Collection;

class EloquentReportRepository implements ReportRepository
{
    public function salesByRange(string $from, string $to): Collection
    {
        return Sale::query()->whereBetween('created_at', [$from, $to])->selectRaw('DATE(created_at) as day, COUNT(*) as total_sales, SUM(total) as amount')->groupBy('day')->orderBy('day')->get();
    }

    public function bestSelling(string $from, string $to): Collection
    {
        return SaleItem::query()->whereHas('sale', fn ($query) => $query->whereBetween('created_at', [$from, $to]))->selectRaw('product_id, SUM(base_quantity) as quantity')->groupBy('product_id')->orderByDesc('quantity')->with('product')->get();
    }

    public function creditsDue(string $until): Collection
    {
        return Credit::query()->where('balance', '>', 0)->whereDate('due_date', '<=', $until)->with(['customer', 'sale'])->get();
    }
}
