<?php

namespace App\Filament\Production\Widgets;

use App\Models\ProductionReport;
use App\Models\JobOrder;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Carbon\Carbon;

class FloorEfficiencyStats extends BaseWidget
{
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        // 1. Production Yield (Planned vs Actual) - Last 7 Days
        $plannedQty = \App\Models\ProductionPlanItem::whereHas('productionPlan', function($q) {
                $q->where('week_start', '>=', now()->subDays(7));
            })->sum('planned_quantity');
            
        $actualQty = \App\Models\ProductionReportItem::where('date', '>=', now()->subDays(7))
            ->sum('actual_quantity');
            
        $yieldPercentage = $plannedQty > 0 ? ($actualQty / $plannedQty) * 100 : 0;

        // 2. Active Machines (those mentioned in recent reports today)
        $activeMachines = \App\Models\ProductionReportItem::whereDate('date', now())
            ->distinct('machine_id')
            ->count();

        // 3. Job Tasks in Queue
        $pendingTasks = \App\Models\JobOrderTask::where('status', 'pending')->count();

        return [
            Stat::make('7D Production Yield', round($yieldPercentage, 1) . '%')
                ->description('Planned vs Actual output')
                ->descriptionIcon($yieldPercentage > 90 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($yieldPercentage > 90 ? 'success' : 'warning'),
            
            Stat::make('Machines Active Today', $activeMachines)
                ->description('Machines with logs today')
                ->color('primary'),
                
            Stat::make('Tasks in Queue', $pendingTasks)
                ->description('Job tasks awaiting start')
                ->color('info'),
        ];
    }
}
