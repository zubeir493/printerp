<?php

namespace App\Filament\Warehouse\Widgets;

use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class LogisticsPulseChart extends ChartWidget
{
    protected ?string $heading = 'Daily Logistics Pulse';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $inbound = [];
        $outbound = [];
        $labels = [];

        for($i=13; $i>=0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('M d');
            
            $inbound[] = \App\Models\GoodsReceipt::whereDate('created_at', $date)->count();
            $outbound[] = \App\Models\Dispatch::whereDate('created_at', $date)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Inbound (Goods Receipts)',
                    'data' => $inbound,
                    'backgroundColor' => '#3b82f6', // Blue
                ],
                [
                    'label' => 'Outbound (Dispatches)',
                    'data' => $outbound,
                    'backgroundColor' => '#10b981', // Emerald
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
