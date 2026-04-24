<?php

namespace App\Filament\Design\Widgets;

use App\Models\Artwork;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DesignPanelStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pendingArtworks = Artwork::query()
            ->where('is_approved', false)
            ->count();

        return [
            Stat::make('Pending Artworks', $pendingArtworks)
                ->description('Artworks awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingArtworks > 0 ? 'warning' : 'success'),
        ];
    }
}
