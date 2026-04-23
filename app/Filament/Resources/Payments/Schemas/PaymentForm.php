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
                    ->label('Payment #')
                    ->default(function () {
                        $lastPayment = \App\Models\Payment::orderBy('id', 'desc')->first();
                        $lastNumber = 0;
                        if ($lastPayment && preg_match('/PAY-(\d+)/', $lastPayment->payment_number, $matches)) {
                            $lastNumber = (int) $matches[1];
                        }
                        return 'PAY-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                    })
                    ->readOnly()
                    ->required(),
                Select::make('payment_type')
                    ->options([
                        'standard' => 'Standard (AR/AP)',
                        'petty_cash' => 'Petty Cash',
                        'expense' => 'Direct Expense',
                    ])
                    ->default('standard')
                    ->required()
                    ->live(),
                Select::make('account_id')
                    ->label('Account')
                    ->relationship('account', 'name')
                    ->options(function (callable $get) {
                        $type = $get('payment_type');
                        $query = \App\Models\Account::query();
                        
                        if ($type === 'petty_cash') {
                            $query->where('name', 'like', '%Petty Cash%');
                        } elseif ($type === 'expense') {
                            $query->where('type', 'Expense');
                        } else {
                            $query->whereIn('code', [\App\Models\Account::CODE_CASH]);
                        }
                        
                        return $query->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required(fn($get) => in_array($get('payment_type'), ['petty_cash', 'expense'])),
                Select::make('partner_id')
                    ->relationship('partner', 'name')
                    ->required(),
                DatePicker::make('payment_date')
                    ->default(now())
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->suffix('Birr'),
                Select::make('direction')
                    ->options([
                        'inbound' => 'Inbound (Customer)',
                        'outbound' => 'Outbound (Vendor/Payroll)',
                    ])
                    ->default('inbound')
                    ->required(),
                Select::make('method')
                    ->options([
                        'cash' => 'Cash',
                        'bank' => 'Bank',
                        'cheque' => 'Cheque',
                    ])
                    ->default('cash')
                    ->required(),
                TextInput::make('reference'),
            ]);
    }
}
