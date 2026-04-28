<?php

namespace App\Filament\Resources\BankTransfers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BankTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('transfer_number')
                    ->label('Transfer Number')
                    ->disabled()
                    ->helperText('Auto-generated transfer reference'),
                
                Select::make('from_bank_id')
                    ->label('From Bank')
                    ->relationship('fromBank', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('to_bank_id', null))
                    ->helperText('Select the source bank account'),
                
                Select::make('to_bank_id')
                    ->label('To Bank')
                    ->relationship('toBank', 'name', function ($query, callable $get) {
                        return $query->where('id', '!=', $get('from_bank_id'));
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (callable $get) => !$get('from_bank_id'))
                    ->helperText('Select the destination bank account'),
                
                TextInput::make('amount')
                    ->label('Transfer Amount')
                    ->required()
                    ->numeric()
                    ->suffix(' Birr')
                    ->rules(['min:0.01'])
                    ->helperText('Amount to transfer between banks'),
                
                DatePicker::make('transfer_date')
                    ->label('Transfer Date')
                    ->required()
                    ->default(now())
                    ->helperText('Date when the transfer occurred'),
                
                Select::make('status')
                    ->label('Transfer Status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending')
                    ->required()
                    ->helperText('Current status of this transfer'),
                
                TextInput::make('reference')
                    ->label('Reference Number')
                    ->maxLength(255)
                    ->helperText('Optional reference or transaction ID from bank'),
                
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->helperText('Purpose or description of this transfer'),
            ]);
    }
}
