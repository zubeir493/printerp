<?php

namespace App\Filament\Production\Widgets;

use App\Models\MaterialRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductionPanelStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $openMaterialRequests = MaterialRequest::query()
            ->whereColumn('issued_quantity', '<', 'required_quantity')
            ->count();

        return [
            Stat::make('Open Material Requests', $openMaterialRequests)
                ->description('Required quantity still not fully issued')
                ->descriptionIcon('heroicon-m-archive-box-arrow-down')
                ->color($openMaterialRequests > 0 ? 'warning' : 'success'),
        ];
    }
}
