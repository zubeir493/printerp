<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Pages\Page;
use App\Models\InventoryBalance;

class StockOverview extends Page implements HasForms, HasTable
{
    protected string $view = 'filament.pages.stock-overview';

    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    public ?int $warehouse_id = null;


    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(\App\Models\Warehouse::pluck('name', 'id'))
                    ->required()
                    ->live()
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {

                if (!$this->warehouse_id) {
                    return InventoryBalance::query()->whereRaw('1 = 0');
                }

                return InventoryBalance::query()
                    ->with('inventoryItem')
                    ->where('warehouse_id', $this->warehouse_id)
                    ->where('quantity_on_hand', '>', 0);
            })
            ->columns([
                Tables\Columns\TextColumn::make('inventoryItem.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity_on_hand')
                    ->label('Quantity')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        number_format($state) . ' ' . $record->inventoryItem->unit
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('inventoryItem.average_cost')
                    ->money('ETB')
                    ->label('Avg Cost'),

                Tables\Columns\TextColumn::make('total_value')
                    ->state(
                        fn($record) =>
                        (float)$record->quantity_on_hand *
                            (float)($record->inventoryItem->average_cost ?? 0)
                    )
                    ->money('ETB')
                    ->color('success')
                    ->label('Total Value'),
            ])
            ->emptyStateHeading('Selected warehouse is empty')
            ->emptyStateDescription('This warehouse currently has no inventory.')
            ->defaultSort('inventoryItem.name');
    }


    public function getTotalValueProperty()
    {
        return InventoryBalance::query()
            ->when(
                $this->warehouse_id,
                fn($query) => $query->where('warehouse_id', $this->warehouse_id)
            )
            ->where('quantity_on_hand', '>', 0)
            ->get()
            ->sum(fn($balance) => (float)$balance->quantity_on_hand * (float)($balance->inventoryItem->average_cost ?? 0));
    }
}
