<?php

namespace App\Filament\Design\Widgets;

use App\Models\Artwork;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class DesignSLAStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1. Avg Approval Time (days)
        $avgApprovalDays = \App\Models\Artwork::where('is_approved', true)
            ->selectRaw('AVG(JULIANDAY(updated_at) - JULIANDAY(created_at)) as avg_days')
            ->value('avg_days') ?? 0;
        
        // 2. Count of Job Orders that are in 'design' status
        $designQueueCount = \App\Models\JobOrder::where('status', 'design')->count();

        // 3. Pending Approvals
        $pendingApprovals = \App\Models\Artwork::where('is_approved', false)->count();

        return [
            Stat::make('Avg Approval Time', round($avgApprovalDays, 1) . ' Days')
                ->description('From upload to approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            
            Stat::make('Job Design Queue', $designQueueCount)
                ->description('Orders awaiting design work')
                ->descriptionIcon('heroicon-m-paint-brush')
                ->color($designQueueCount > 10 ? 'danger' : 'success'),
                
            Stat::make('Pending Internal Approval', $pendingApprovals)
                ->description('Artworks awaiting sign-off')
                ->color('primary'),
        ];
    }
}
