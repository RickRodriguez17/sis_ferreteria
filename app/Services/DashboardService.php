<?php

namespace App\Services;

use App\Domain\Enums\CashMovementType;
use App\Domain\Enums\CashSessionStatus;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Credit;
use App\Models\Inventory;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function summary(): array
    {
        $today = Carbon::today();
        $openSession = CashSession::query()->with('register')->where('status', CashSessionStatus::Open)->latest('opened_at')->first();
        $salesToday = Sale::query()->whereDate('created_at', $today)->where('status', 'completed');
        $lowStock = Inventory::query()->with(['product', 'location'])->whereHas('product', fn ($q) => $q->whereColumn('inventory.quantity', '<=', 'products.min_stock'))->orderBy('quantity')->limit(8)->get();
        $bestSelling = SaleItem::query()->with('product')->selectRaw('product_id, SUM(quantity) as quantity')->whereHas('sale', fn ($q) => $q->where('status', 'completed')->where('created_at', '>=', now()->subDays(30)))->groupBy('product_id')->orderByDesc('quantity')->limit(5)->get();
        $recentMovements = CashMovement::query()->with(['reference', 'cashSession'])->latest()->limit(8)->get();

        return [
            'salesToday' => (float) $salesToday->sum('total'),
            'salesCountToday' => $salesToday->count(),
            'purchasesToday' => (float) Purchase::query()->whereDate('created_at', $today)->sum('total'),
            'openSession' => $openSession,
            'expectedCash' => $openSession ? app(CashService::class)->expectedAmount($openSession) : '0.00',
            'creditsBalance' => (float) Credit::query()->where('balance', '>', 0)->sum('balance'),
            'overdueCredits' => Credit::query()->overdue()->count(),
            'lowStock' => $lowStock,
            'lowStockCount' => $lowStock->count(),
            'bestSelling' => $bestSelling,
            'recentMovements' => $recentMovements,
            'cashDifferences' => CashSession::query()->where('status', CashSessionStatus::Closed)->where('difference', '!=', 0)->latest('closed_at')->limit(5)->get(),
            'dueCredits' => Credit::query()->where('balance', '>', 0)->whereBetween('due_date', [today(), today()->addDays(7)])->with('customer')->limit(5)->get(),
            'incomeToday' => (float) CashMovement::query()->whereDate('created_at', $today)->where('type', CashMovementType::Income)->sum('amount'),
        ];
    }
}
