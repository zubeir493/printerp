<?php

namespace App\Filament\Resources\Invoices\Tables;

use Filament\Actions\Action as ActionsAction;
use Filament\Actions\BulkActionGroup as ActionsBulkActionGroup;
use Filament\Actions\DeleteBulkAction as ActionsDeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),
                
                TextColumn::make('invoice_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'sales' => 'success',
                        'purchase' => 'warning', 
                        'service' => 'info',
                        'receipt' => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'sales' => 'Sales',
                        'purchase' => 'Purchase',
                        'service' => 'Service',
                        'receipt' => 'Receipt',
                    }),
                
                TextColumn::make('partner.name')
                    ->label('Customer/Supplier')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('invoice_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->suffix(' ETB')
                    ->sortable()
                    ->alignEnd(),
                
                TextColumn::make('balance_due')
                    ->label('Balance')
                    ->suffix(' ETB')
                    ->sortable()
                    ->alignEnd()
                    ->color(fn($record) => $record->balance_due > 0 ? 'danger' : 'success'),
                
                BadgeColumn::make('status')
                    ->colors([
                        'draft' => 'gray',
                        'sent' => 'info',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'warning',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    }),
                
                IconColumn::make('emailed_at')
                    ->label('Emailed')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                SelectFilter::make('invoice_type')
                    ->label('Type')
                    ->options([
                        'sales' => 'Sales Invoices',
                        'purchase' => 'Purchase Invoices',
                        'service' => 'Service Invoices',
                        'receipt' => 'Receipts',
                    ]),
                
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ]),
                
                Filter::make('overdue')
                    ->label('Overdue Only')
                    ->query(fn($query) => $query->overdue())
                    ->toggle(),
                
                Filter::make('unpaid')
                    ->label('Unpaid Only')
                    ->query(fn($query) => $query->where('balance_due', '>', 0))
                    ->toggle(),
            ])
            ->actions([
                ActionsAction::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn($record) => $record->file_path)
                    ->openUrlInNewTab(),
                
                ActionsAction::make('resend_email')
                    ->label('Resend Email')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->visible(fn($record) => $record->email_recipient && $record->status !== 'cancelled')
                    ->action(function ($record) {
                        try {
                            $invoiceService = app(\App\Services\InvoiceGeneratorService::class);
                            $sent = $invoiceService->sendInvoiceEmail(
                                [
                                    'invoice_data' => [
                                        'invoice_number' => $record->invoice_number,
                                        'partner' => $record->partner,
                                    ]
                                ],
                                $record->email_recipient
                            );
                            
                            if ($sent) {
                                $record->update(['emailed_at' => now()]);
                                \Filament\Notifications\Notification::make()
                                    ->title('Email Resent')
                                    ->body('Invoice resent to ' . $record->email_recipient)
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Email Failed')
                                    ->body('Failed to resend invoice')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Email Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                ActionsBulkActionGroup::make([
                    ActionsDeleteBulkAction::make(),
                ]),
            ]);
    }
}
