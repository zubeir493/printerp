<?php

namespace App\Filament\Resources\JobOrders;

use App\Filament\Resources\JobOrders\Pages\CreateJobOrder;
use App\Filament\Resources\JobOrders\Pages\EditJobOrder;
use App\Filament\Resources\JobOrders\Pages\ListJobOrders;
use App\Filament\Resources\JobOrders\Schemas\JobOrderForm;
use App\Filament\Resources\JobOrders\Tables\JobOrdersTable;
use App\Models\JobOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class JobOrderResource extends Resource
{
    protected static ?string $model = JobOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return JobOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JobOrdersTable::configure($table)
            ->columns([
                Tables\Columns\TextColumn::make('job_order_number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('partner.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'design' => 'info',
                        'production' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('total_price')->suffix(' Birr'),
                Tables\Columns\IconColumn::make('advance_paid')
                    ->boolean()
                    ->label('Adv. Paid'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\JobOrders\RelationManagers\ArtworksRelationManager::class,
            \App\Filament\Resources\JobOrders\RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobOrders::route('/'),
            'create' => CreateJobOrder::route('/create'),
            'edit' => EditJobOrder::route('/{record}/edit'),
        ];
    }
}
