<?php

namespace App\Filament\Resources\PaymentAllocations;

use App\Filament\Resources\PaymentAllocations\Pages\CreatePaymentAllocation;
use App\Filament\Resources\PaymentAllocations\Pages\ListPaymentAllocations;
use App\Filament\Resources\PaymentAllocations\Schemas\PaymentAllocationForm;
use App\Filament\Resources\PaymentAllocations\Tables\PaymentAllocationsTable;
use App\Models\PaymentAllocation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PaymentAllocationResource extends Resource
{
    protected static ?string $model = PaymentAllocation::class;

    protected static ?string $navigationParentItem = 'Payments';

    protected static ?string $navigationLabel = 'Allocations';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return PaymentAllocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentAllocationsTable::configure($table)
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
            'index' => ListPaymentAllocations::route('/'),
            'view' => \App\Filament\Resources\PaymentAllocations\Pages\ViewPaymentAllocation::route('/{record}'),
        ];
    }
}
