<?php

namespace App\Filament\Resources\JobOrders\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
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
use App\Models\Payment;

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
                    ->label('Payment #')
                    ->searchable(),
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
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data, string $model): \Illuminate\Database\Eloquent\Model {
                        return DB::transaction(function () use ($data) {
                            $jobOrder = $this->getOwnerRecord();
                            
                            // 1. Create the Payment
                            $nextId = (Payment::max('id') ?? 0) + 1;
                            $paymentNumber = 'PAY-JO-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
                            
                            $payment = Payment::create([
                                'payment_number' => $paymentNumber,
                                'partner_id' => $jobOrder->partner_id,
                                'amount' => $data['allocated_amount'],
                                'direction' => 'inbound',
                                'method' => $data['method'],
                                'reference' => $data['reference'] ?? null,
                                'payment_date' => $data['payment_date'],
                            ]);

                            // 2. Create the Allocation (this return value is what Filament expects)
                            return $jobOrder->paymentAllocations()->create([
                                'payment_id' => $payment->id,
                                'allocated_amount' => $data['allocated_amount'],
                            ]);
                        });
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
