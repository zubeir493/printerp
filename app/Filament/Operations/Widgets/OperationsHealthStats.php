<?php

namespace App\Filament\Operations\Widgets;

use App\Models\JobOrder;
use App\Models\PurchaseOrder;
use App\Models\Dispatch;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Carbon\Carbon;

class OperationsHealthStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $activeJobOrders = JobOrder::whereIn('status', ['planned', 'in_production'])->count();
        $pendingPurchases = PurchaseOrder::where('status', 'pending')->count();
        $dispatchesToday = Dispatch::whereDate('created_at', Carbon::today())->count();

        return [
            Stat::make('Active Job Orders', $activeJobOrders)
                ->description('In pipeline')
                ->descriptionIcon('heroicon-m-wrench')
                ->color('primary'),
            
            Stat::make('Pending Purchases', $pendingPurchases)
                ->description('Awaiting delivery')
                ->color($pendingPurchases > 10 ? 'warning' : 'success'),
                
            Stat::make('Dispatches Today', $dispatchesToday)
                ->description('Outbound shipments')
                ->descriptionIcon('heroicon-m-truck')
                ->color('success'),
        ];
    }
}
