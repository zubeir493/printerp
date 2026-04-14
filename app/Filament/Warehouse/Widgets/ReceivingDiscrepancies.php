<?php

namespace App\Filament\Warehouse\Widgets;

use App\Models\GoodsReceipt;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ReceivingDiscrepancies extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Receiving Discrepancies (PO vs Actual)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                GoodsReceipt::query()
                    ->whereColumn('received_quantity', '!=', 'expected_quantity') // Pseudo-code if columns exist
                    ->orWhere('status', 'partial') // Assuming partial means discrepancy
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('Receipt #')
                    ->searchable(),
                Tables\Columns\TextColumn::make('purchaseOrder.order_number')
                    ->label('PO #'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color('danger')
                    ->default('Mismatch'),
                Tables\Columns\TextColumn::make('created_at')
                    ->date()
            ]);
    }
}
