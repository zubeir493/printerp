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
                    'label' => 'Bottlenecks',
                    'data' => [$joPending, $artworkPending, $poPending],
                    'backgroundColor' => [
                        '#f59e0b', // Amber
                        '#ef4444', // Red
                        '#3b82f6', // Blue
                    ],
                ],
            ],
            'labels' => ['Draft Job Orders', 'Pending Artworks', 'Awaiting Purchases'],
        ];
    }

    protected function getType(): string
    {
        return 'polarArea';
    }
}
