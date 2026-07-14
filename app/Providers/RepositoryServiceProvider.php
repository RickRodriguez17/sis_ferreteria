<?php

namespace App\Providers;

use App\Repositories\Contracts\InventoryRepository;
use App\Repositories\Contracts\KardexRepository;
use App\Repositories\Contracts\ReportRepository;
use App\Repositories\Eloquent\EloquentInventoryRepository;
use App\Repositories\Eloquent\EloquentKardexRepository;
use App\Repositories\Eloquent\EloquentReportRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(KardexRepository::class, EloquentKardexRepository::class);
        $this->app->bind(InventoryRepository::class, EloquentInventoryRepository::class);
        $this->app->bind(ReportRepository::class, EloquentReportRepository::class);
    }
}
