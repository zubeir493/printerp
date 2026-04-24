<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentTransactionType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Details')
                    ->description('Choose the preset and enter the business facts. Accounting accounts are handled automatically.')
                    ->schema([
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
                        Select::make('transaction_type')
                            ->label('Transaction Type')
                            ->options(PaymentTransactionType::options())
                            ->default(PaymentTransactionType::CUSTOMER_RECEIPT->value)
                            ->helperText(function (callable $get) {
                                return PaymentTransactionType::tryFrom(
                                    $get('transaction_type') ?? PaymentTransactionType::CUSTOMER_RECEIPT->value
                                )?->description();
                            })
                            ->required()
                            ->live(),
                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->suffix('Birr'),
                        DatePicker::make('payment_date')
                            ->default(now())
                            ->required(),
                        Select::make('method')
                            ->label('Paid Via')
                            ->options([
                                'cash' => 'Cash',
                                'bank' => 'Bank Transfer',
                                'cheque' => 'Cheque',
                            ])
                            ->default('cash')
                            ->afterStateHydrated(function (Select $component, $record) {
                                if (! $record) {
                                    return;
                                }

                                $component->state(match ($record->method) {
                                    'bank_transfer' => 'bank',
                                    'check' => 'cheque',
                                    default => $record->method,
                                });
                            })
                            ->required(),
                        TextInput::make('reference')
                            ->label('Memo / Reference')
                            ->placeholder('For example: receipt number, bill number, or short note')
                            ->helperText('Use this field for later lookup and audit reference.')
                            ->maxLength(255),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
                Section::make('Counterparty')
                    ->description('Shown only for customer receipts and supplier payments.')
                    ->schema([
                        Select::make('partner_id')
                            ->label(fn (callable $get) => PaymentTransactionType::tryFrom(
                                $get('transaction_type') ?? PaymentTransactionType::CUSTOMER_RECEIPT->value
                            )?->partnerLabel() ?? 'Counterparty')
                            ->options(function (callable $get) {
                                $type = PaymentTransactionType::tryFrom($get('transaction_type') ?? PaymentTransactionType::CUSTOMER_RECEIPT->value)
                                    ?? PaymentTransactionType::CUSTOMER_RECEIPT;

                                $query = \App\Models\Partner::query();

                                if ($type === PaymentTransactionType::CUSTOMER_RECEIPT) {
                                    $query->where('is_customer', true);
                                } elseif ($type === PaymentTransactionType::SUPPLIER_PAYMENT) {
                                    $query->where('is_supplier', true);
                                }

                                return $query->orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required(fn (callable $get) => PaymentTransactionType::tryFrom(
                                $get('transaction_type') ?? PaymentTransactionType::CUSTOMER_RECEIPT->value
                            )?->requiresPartner() ?? false)
                            ->visible(fn (callable $get) => PaymentTransactionType::tryFrom(
                                $get('transaction_type') ?? PaymentTransactionType::CUSTOMER_RECEIPT->value
                            )?->requiresPartner() ?? false),
                    ])
                    ->columnSpanFull()
                    ->visible(fn (callable $get) => PaymentTransactionType::tryFrom(
                        $get('transaction_type') ?? PaymentTransactionType::CUSTOMER_RECEIPT->value
                    )?->requiresPartner() ?? false),
            ]);
    }
}
