<?php

namespace App\Filament\Widgets;

use App\Models\JobOrder;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class JobOrdersStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalJobs = JobOrder::count();
        $totalValue = JobOrder::sum('total_price');
        $activeJobs = JobOrder::whereIn('status', ['design', 'production'])->count();
        $lateJobs = JobOrder::late()->count();
        $completedToday = JobOrder::where('status', 'completed')
            ->whereDate('updated_at', today())
            ->count();

        // Sample chart data
        $activeChart = [10, 12, 8, 15, 11, 9, $activeJobs];
        $lateChart = [1, 2, 1, 3, 2, 4, $lateJobs];
        $completedChart = [2, 3, 1, 4, 2, 3, $completedToday];

        return [
            Stat::make('Active Jobs', $activeJobs)
                ->description('In design/production')
                ->descriptionIcon('heroicon-m-cog')
                ->color($activeJobs > 20 ? 'warning' : 'primary')
                ->chart($activeChart),
            Stat::make('Late Job Orders', $lateJobs)
                ->description('Submission date is before today')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lateJobs > 0 ? 'danger' : 'success')
                ->chart($lateChart),
            Stat::make('Completed Today', $completedToday)
                ->description('Finished orders')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart($completedChart),
        ];
    }
}
