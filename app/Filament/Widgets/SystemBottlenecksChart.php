<?php

namespace App\Filament\Widgets;

use App\Models\JobOrder;
use App\Models\Artwork;
use App\Models\PurchaseOrder;
use Filament\Widgets\ChartWidget;

class SystemBottlenecksChart extends ChartWidget
{
    protected ?string $heading = 'System Bottlenecks';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $joPending = \App\Models\JobOrder::where('status', 'draft')->count();
        $artworkPending = \App\Models\Artwork::where('is_approved', false)->count();
        $poPending = \App\Models\PurchaseOrder::where('status', 'pending')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Pending Items',
                    'data' => [$joPending, $artworkPending, $poPending],
                    'backgroundColor' => [
                        'rgba(245, 158, 11, 0.8)', // Amber
                        'rgba(239, 68, 68, 0.8)', // Red
                        'rgba(59, 130, 246, 0.8)', // Blue
                    ],
                    'borderColor' => [
                        '#f59e0b',
                        '#ef4444',
                        '#3b82f6',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Draft Job Orders', 'Pending Artworks', 'Awaiting Purchases'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
