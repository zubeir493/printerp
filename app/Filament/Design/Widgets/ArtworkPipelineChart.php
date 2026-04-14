<?php

namespace App\Filament\Design\Widgets;

use App\Models\Artwork;
use Filament\Widgets\ChartWidget;

class ArtworkPipelineChart extends ChartWidget
{
    protected ?string $heading = 'Artwork Pipeline';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $approved = \App\Models\Artwork::where('is_approved', true)->count();
        $pending = \App\Models\Artwork::where('is_approved', false)->count();

        return [
            'datasets' => [
                [
                    'label' => 'Artworks',
                    'data' => [$approved, $pending],
                    'backgroundColor' => [
                        '#10b981', // Green - Approved
                        '#f59e0b', // Amber - Pending
                    ],
                ],
            ],
            'labels' => ['Approved', 'Awaiting Approval'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
