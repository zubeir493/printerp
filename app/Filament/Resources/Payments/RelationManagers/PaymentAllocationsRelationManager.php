<?php

namespace App\Filament\Resources\Payments\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentAllocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentAllocations';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        $transactionType = $ownerRecord->transaction_type ?? match ($ownerRecord->payment_type ?? null) {
            'expense' => 'direct_expense',
            'petty_cash' => $ownerRecord->direction === 'inbound' ? 'petty_cash_funding' : 'petty_cash_expense',
            default => $ownerRecord->direction === 'outbound' ? 'supplier_payment' : 'customer_receipt',
        };

        return in_array($transactionType, ['customer_receipt', 'supplier_payment'], true);
    }

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
                    ->badge()
                    ->color(fn($state) => match($state) {
                        \App\Models\JobOrder::class => 'info',
                        \App\Models\SalesOrder::class => 'success',
                        \App\Models\PurchaseOrder::class => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        \App\Models\JobOrder::class => 'Job Order',
                        \App\Models\SalesOrder::class => 'Sales Order',
                        \App\Models\PurchaseOrder::class => 'Purchase Order',
                        default => str_replace('App\\Models\\', '', $state),
                    }),
                TextColumn::make('allocatable.id')
                    ->label('Reference')
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->formatStateUsing(function ($record) {
                        if ($record->allocatable_type === \App\Models\JobOrder::class) return $record->allocatable->job_order_number;
                        if ($record->allocatable_type === \App\Models\SalesOrder::class) return $record->allocatable->order_number;
                        if ($record->allocatable_type === \App\Models\PurchaseOrder::class) return $record->allocatable->po_number;
                        return $record->allocatable_id;
                    })
                    ->description(fn($record) => $record->allocatable?->partner?->name),
                TextColumn::make('allocated_amount')
                    ->label('Allocated')
                    ->suffix(' ETB')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                DeleteAction::make(),
            ]);
    }
}
