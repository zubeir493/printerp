<?php

namespace App\Filament\Resources\StockAdjustments;

use App\Filament\Support\PanelAccess;
use App\Filament\Resources\StockAdjustments\Pages\CreateStockAdjustment;
use App\Filament\Resources\StockAdjustments\Pages\EditStockAdjustment;
use App\Filament\Resources\StockAdjustments\Pages\ListStockAdjustments;
use App\Filament\Resources\StockAdjustments\Schemas\StockAdjustmentForm;
use App\Filament\Resources\StockAdjustments\Tables\StockAdjustmentsTable;
use App\Models\StockAdjustment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    public static function form(Schema $schema): Schema
    {
        return StockAdjustmentForm::configure($schema);
    }

    public static function canViewAny(): bool
    {
        return PanelAccess::canAccessWarehouseSection();
    }



    public static function table(Table $table): Table
    {
        return StockAdjustmentsTable::configure($table)
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockAdjustments::route('/'),
            'create' => CreateStockAdjustment::route('/create'),
            'view' => \App\Filament\Resources\StockAdjustments\Pages\ViewStockAdjustment::route('/{record}'),
            'edit' => EditStockAdjustment::route('/{record}/edit'),
        ];
    }
}
