<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface ReportRepository
{
    public function salesByRange(string $from, string $to): Collection;

    public function bestSelling(string $from, string $to): Collection;

    public function creditsDue(string $until): Collection;
}
