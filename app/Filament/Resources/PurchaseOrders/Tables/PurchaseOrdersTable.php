<?php

namespace App\Filament\Resources\PurchaseOrders\Tables;

use App\Models\PurchaseOrder;
use App\Filament\Support\PanelAccess;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
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
                EditAction::make()
                    ->visible(fn () => PanelAccess::canManagePurchaseOrders()),
                Action::make('invoice')
                    ->label('Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->action(function ($record) {
                        try {
                            $invoiceService = app(\App\Services\InvoiceGeneratorService::class);
                            $result = $invoiceService->generateFromPurchaseOrder($record);
                            
                            $actions = [
                                Action::make('download')
                                    ->label('Download')
                                    ->url($invoiceService->getInvoicePath($result['filename']))
                                    ->openUrlInNewTab(),
                            ];

                            // Only add email action if partner has email
                            if ($record->partner && $record->partner->email) {
                                $actions[] = Action::make('email')
                                    ->label('Email Invoice')
                                    ->icon('heroicon-o-envelope')
                                    ->action(function () use ($record, $result, $invoiceService) {
                                        $sent = $invoiceService->sendInvoiceEmail(
                                            $result, 
                                            $record->partner->email
                                        );
                                        
                                        if ($sent) {
                                            // Update invoice record with email info
                                            $result['invoice']->update([
                                                'emailed_at' => now(),
                                                'email_recipient' => $record->partner->email,
                                            ]);

                                            \Filament\Notifications\Notification::make()
                                                ->title('Invoice Sent')
                                                ->body('Invoice emailed to ' . $record->partner->email)
                                                ->success()
                                                ->send();
                                        } else {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Email Failed')
                                                ->body('Failed to send invoice. Please check email configuration.')
                                                ->danger()
                                                ->send();
                                        }
                                    });
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Invoice Generated')
                                ->body('Invoice ' . $result['invoice_data']['invoice_number'] . ' created successfully.')
                                ->success()
                                ->actions($actions)
                                ->send();
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
                    DeleteBulkAction::make()
                        ->visible(fn () => PanelAccess::canManagePurchaseOrders()),
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\PurchaseOrderExporter::class)
                        ->visible(fn () => PanelAccess::canManagePurchaseOrders())
                ]),
            ]);
    }
}
