<?php

namespace App\Filament\Resources\PurchaseOrders\Tables;

use App\Models\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_number')
                    ->label('PO #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->description(fn($record) => $record->partner?->name),
                TextColumn::make('order_date')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->description(fn($record) => $record->purchaseOrderItems()->count() . ' items'),
                TextColumn::make('subtotal')
                    ->label('Total')
                    ->suffix(' ETB')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->suffix(' ETB')
                    ->sortable()
                    ->color(fn($record) => $record->balance > 0 ? 'warning' : 'success'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'approved' => 'info',
                        'received' => 'success',
                        'cancelled' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'approved' => 'Approved',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    }),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'approved' => 'Approved',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('partner_id')
                    ->label('Supplier')
                    ->options(\App\Models\Partner::where('is_supplier', true)->pluck('name', 'id')->toArray()),
                \Filament\Tables\Filters\Filter::make('unpaid')
                    ->label('Unpaid Only')
                    ->query(fn($query) => $query->where('balance', '>', 0))
                    ->toggle(),
            ])
            ->recordActions([
                EditAction::make(),
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
                            $result = $invoiceService->generateFromPurchaseOrder($record);
                            
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
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\PurchaseOrderExporter::class)
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\PurchaseOrderExporter::class)
                ]),
            ]);
    }
}
