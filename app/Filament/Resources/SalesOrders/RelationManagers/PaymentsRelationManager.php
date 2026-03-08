<?php

namespace App\Filament\Resources\SalesOrders\RelationManagers;

use App\Models\Payment;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentAllocations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('payment_number')
                    ->dehydrated(false),

                TextInput::make('allocated_amount')
                    ->label('Amount')
                    ->numeric()
                    ->required()
                    ->suffix('Birr'),

                Select::make('method')
                    ->options([
                        'cash' => 'Cash',
                        'bank' => 'Bank',
                        'cheque' => 'Cheque',
                    ])
                    ->required(),

                TextInput::make('reference')
                    ->maxLength(255),

                DatePicker::make('payment_date')
                    ->default(now())
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_number')
            ->columns([
                TextColumn::make('payment.payment_number')
                    ->label('Payment #'),
                TextColumn::make('payment.payment_date')
                    ->label('Date')
                    ->date(),
                TextColumn::make('allocated_amount')
                    ->label('Allocated')
                    ->suffix(' Birr'),
                TextColumn::make('payment.method')
                    ->label('Method'),
                TextColumn::make('payment.reference')
                    ->label('Reference'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data, string $model): \Illuminate\Database\Eloquent\Model {
                        return DB::transaction(function () use ($data) {
                            $salesOrder = $this->getOwnerRecord();
                            
                            $nextId = (Payment::max('id') ?? 0) + 1;
                            $paymentNumber = 'PAY-SO-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
                            
                            $payment = Payment::create([
                                'payment_number' => $paymentNumber,
                                'partner_id' => $salesOrder->partner_id,
                                'amount' => $data['allocated_amount'],
                                'direction' => 'inbound',
                                'method' => $data['method'],
                                'reference' => $data['reference'] ?? null,
                                'payment_date' => $data['payment_date'],
                            ]);

                            return $salesOrder->paymentAllocations()->create([
                                'payment_id' => $payment->id,
                                'allocated_amount' => $data['allocated_amount'],
                            ]);
                        });
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
