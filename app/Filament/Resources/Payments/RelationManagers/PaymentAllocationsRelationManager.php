<?php

namespace App\Filament\Resources\Payments\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentAllocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentAllocations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('allocatable_type')
                    ->options([
                        \App\Models\JobOrder::class => 'Job Order',
                        \App\Models\SalesOrder::class => 'Sales Order',
                        \App\Models\PurchaseOrder::class => 'Purchase Order',
                    ])
                    ->required()
                    ->reactive(),

                Select::make('allocatable_id')
                    ->label('Document')
                    ->options(function (callable $get) {
                        $type = $get('allocatable_type');
                        if (!$type) return [];
                        
                        return $type::all()->pluck('id', 'id')->map(function ($id, $originalId) use ($type) {
                            $record = $type::find($originalId);
                            if ($type === \App\Models\JobOrder::class) return $record->job_order_number;
                            if ($type === \App\Models\SalesOrder::class) return $record->order_number;
                            if ($type === \App\Models\PurchaseOrder::class) return $record->po_number;
                            return $id;
                        });
                    })
                    ->required(),

                TextInput::make('allocated_amount')
                    ->numeric()
                    ->required()
                    ->suffix('Birr'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('allocatable_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => str_replace('App\\Models\\', '', $state)),
                TextColumn::make('allocatable.id')
                    ->label('Ref #')
                    ->formatStateUsing(function ($record) {
                        if ($record->allocatable_type === \App\Models\JobOrder::class) return $record->allocatable->job_order_number;
                        if ($record->allocatable_type === \App\Models\SalesOrder::class) return $record->allocatable->order_number;
                        if ($record->allocatable_type === \App\Models\PurchaseOrder::class) return $record->allocatable->po_number;
                        return $record->allocatable_id;
                    }),
                TextColumn::make('allocated_amount')
                    ->suffix(' Birr'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
