<?php

namespace App\Filament\Resources\SalesOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Sales Order')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('partner.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'completed' => 'success',
                        'void' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('payment_mode')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state))
                    ->description(fn ($record) => $record->total . ' birr')
                    ->color(fn ($state) => $state === 'cash' ? 'success' : 'warning'),
                TextColumn::make('balance')
                    ->label('Unpaid')
                    ->state(fn ($record) => number_format($record->balance, 2) . ' Birr')
                    ->color(fn ($record) => $record->balance > 0 ? 'warning' : 'success')
                    ->description(fn ($record) => $record->paymentAllocations()->count() > 0 
                        ? $record->paymentAllocations()->count() . ' payment(s)' 
                        : 'No payments'),
                TextColumn::make('order_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'completed' => 'Completed',
                        'void' => 'Void',
                    ]),
                SelectFilter::make('payment_mode')
                    ->label('Payment Type')
                    ->options([
                        'cash' => 'Cash',
                        'credit' => 'Credit',
                    ]),
                SelectFilter::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(\App\Models\Warehouse::orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('invoice')
                    ->label('Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->visible(fn ($record) => $record->paymentAllocations()->count() > 0)
                    ->form([
                        \Filament\Forms\Components\Radio::make('action')
                            ->label('Action')
                            ->options([
                                'generate' => 'Generate Invoice (Download)',
                                'email' => 'Email Invoice to Customer',
                            ])
                            ->default('generate')
                            ->required(),
                    ])
                    ->action(function (array $data, $record) {
                        try {
                            $invoiceService = app(\App\Services\InvoiceGeneratorService::class);
                            $result = $invoiceService->generateFromSalesOrder($record);
                            
                            if ($data['action'] === 'email') {
                                $sent = $invoiceService->sendInvoiceEmail(
                                    $result, 
                                    $record->partner->email
                                );
                                
                                if ($sent) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Invoice Sent')
                                        ->body('Invoice ' . $result['invoice_data']['invoice_number'] . ' emailed to ' . $record->partner->email)
                                        ->success()
                                        ->actions([
                                            \Filament\Notifications\Actions\Action::make('download')
                                                ->label('Download Copy')
                                                ->url($invoiceService->getInvoicePath($result['filename']))
                                                ->openUrlInNewTab(),
                                        ])
                                        ->send();
                                } else {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Email Failed')
                                        ->body('Failed to send invoice. Please check email configuration.')
                                        ->danger()
                                        ->actions([
                                            \Filament\Notifications\Actions\Action::make('download')
                                                ->label('Download Invoice')
                                                ->url($invoiceService->getInvoicePath($result['filename']))
                                                ->openUrlInNewTab(),
                                        ])
                                        ->send();
                                }
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Invoice Generated')
                                    ->body('Invoice ' . $result['invoice_data']['invoice_number'] . ' created successfully.')
                                    ->success()
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('download')
                                            ->label('Download')
                                            ->url($invoiceService->getInvoicePath($result['filename']))
                                            ->openUrlInNewTab(),
                                        \Filament\Notifications\Actions\Action::make('email')
                                            ->label('Email Invoice')
                                            ->icon('heroicon-o-envelope')
                                            ->action(function () use ($record, $result, $invoiceService) {
                                                $sent = $invoiceService->sendInvoiceEmail(
                                                    $result, 
                                                    $record->partner->email
                                                );
                                                
                                                if ($sent) {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Invoice Sent')
                                                        ->body('Invoice emailed to ' . $record->partner->email)
                                                        ->success()
                                                        ->send();
                                                } else {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Email Failed')
                                                        ->body('Failed to send invoice')
                                                        ->danger()
                                                        ->send();
                                                }
                                            }),
                                    ])
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Invoice Action Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                \Filament\Actions\Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'completed')
                    ->requiresConfirmation()
                    ->modalHeading('Void Sales Order')
                    ->modalDescription('Are you sure you want to void this sales order? This action cannot be undone.')
                    ->modalSubmitActionLabel('Void Order')
                    ->action(function ($record) {
                        $record->update(['status' => 'void']);
                        \Filament\Notifications\Notification::make()
                            ->title('Sales Order Voided')
                            ->body($record->order_number . ' has been voided.')
                            ->danger()
                            ->send();
                    }),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\SalesOrderExporter::class),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\SalesOrderExporter::class),
                ]),
            ]);
    }
}
