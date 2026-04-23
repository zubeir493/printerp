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
use Filament\Support\Icons\Heroicon;
use App\Models\InventoryBalance;

use BackedEnum;
use Filament\Tables\Columns\TextColumn;

class StockOverview extends Page implements HasForms, HasTable
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected string $view = 'filament.pages.stock-overview';


    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    public ?int $warehouse_id = null;
    
    public function mount(): void
    {
        $this->warehouse_id = \App\Models\Warehouse::where('is_default', true)->value('id');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\StockOverviewStats::make([
                'warehouse_id' => $this->warehouse_id,
            ]),
        ];
    }


    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(\App\Models\Warehouse::pluck('name', 'id'))
                    ->default(fn() => \App\Models\Warehouse::where('is_default', true)->value('id'))
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
                TextColumn::make('inventoryItem.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quantity_on_hand')
                    ->label('Quantity')
                    ->formatStateUsing(function ($state, $record) {
                        $item = $record->inventoryItem;

                        if (!$item) {
                            return number_format($state);
                        }

                        $type = $item->type instanceof \BackedEnum ? $item->type->value : (string)$item->type;
                        $isRawMaterial = strtolower($type) === 'raw_material'
                            || ($item->purchase_unit && $item->conversion_factor > 0);

                        if ($isRawMaterial && $item->purchase_unit && $item->conversion_factor > 0) {
                            $convertedQuantity = (float)$state / (float)$item->conversion_factor;
                            return number_format($convertedQuantity, 2) . ' ' . $item->purchase_unit;
                        }

                        return number_format($state) . ' ' . $item->unit;
                    })
                    ->sortable(),

                TextColumn::make('total_value')
                    ->state(function ($record) {
                        $item = $record->inventoryItem;
                        if (!$item || in_array($item->type, ['tools', 'spare_parts', 'wip'])) {
                            return null;
                        }

                        $unitPrice = (float)($item->price ?? 0);
                        if ($item->type === 'raw_material' && (float)($item->conversion_factor ?? 0) > 0) {
                            $unitPrice = $unitPrice / (float)$item->conversion_factor;
                        }

                        return (float)$record->quantity_on_hand * $unitPrice;
                    })
                    ->placeholder('-')
                    ->suffix(fn($state) => $state === null ? '' : ' Birr')
                    ->color('success')
                    ->label('Total Value')
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total Warehouse Value')
                            ->using(
                                fn($query) =>
                                (float) $query->join('inventory_items', 'inventory_items.id', '=', 'inventory_balances.inventory_item_id')
                                    ->sum(\Illuminate\Support\Facades\DB::raw('inventory_balances.quantity_on_hand * (CASE WHEN inventory_items.type = "raw_material" AND inventory_items.conversion_factor > 0 THEN CAST(inventory_items.price AS DECIMAL) / CAST(inventory_items.conversion_factor AS DECIMAL) WHEN inventory_items.type IN ("tools", "spare_parts", "wip") THEN 0 ELSE CAST(inventory_items.price AS DECIMAL) END)'))
                            )
                            ->numeric()
                    ),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\InventoryBalanceExporter::class)
            ])
            ->emptyStateHeading('Selected warehouse is empty')
            ->emptyStateDescription('This warehouse currently has no inventory.')
            ->defaultSort('inventoryItem.name');
    }
}
