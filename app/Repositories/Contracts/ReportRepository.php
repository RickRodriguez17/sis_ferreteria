<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface ReportRepository
{
    public function salesByRange(string $from, string $to): Collection;

    public function bestSelling(string $from, string $to): Collection;

    public function creditsDue(string $until): Collection;

    public function salesReport(array $filters = []): Builder;

    public function purchasesReport(array $filters = []): Builder;

    public function inventoryReport(array $filters = []): Builder;

    public function kardexReport(array $filters = []): Builder;

    public function customersReport(array $filters = []): Builder;

    public function suppliersReport(array $filters = []): Builder;

    public function cashReport(array $filters = []): Builder;

    public function creditsReport(array $filters = []): Builder;

    public function bestSellingReport(array $filters = []): Builder;

    public function lowStockReport(array $filters = []): Builder;
}
