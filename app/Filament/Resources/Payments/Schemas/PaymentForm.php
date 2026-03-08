<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('payment_number')
                    ->required(),
                Select::make('partner_id')
                    ->relationship('partner', 'name')
                    ->required(),
                DatePicker::make('payment_date')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Select::make('direction')
                    ->options([
                        'inbound' => 'Inbound (Customer)',
                        'outbound' => 'Outbound (Vendor/Payroll)',
                    ])
                    ->required(),
                Select::make('method')
                    ->options([
                        'cash' => 'Cash',
                        'bank' => 'Bank',
                        'cheque' => 'Cheque',
                    ])
                    ->required(),
                TextInput::make('reference'),
            ]);
    }
}
