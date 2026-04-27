<?php

namespace App\Filament\Resources\JobOrders\Tables;

use App\Filament\Support\PanelAccess;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class JobOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('job_order_number')
                    ->label('Job Order')
                    ->description(fn($record) => $record->partner?->name)
                    ->weight('bold')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'active' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('submission_date')
                    ->label('Submission Date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->submission_date && $record->submission_date->isBefore(today()) && !in_array($record->status, ['completed', 'cancelled']) ? 'danger' : null)
                    ->description(fn ($record) => $record->submission_date && $record->submission_date->isBefore(today()) && !in_array($record->status, ['completed', 'cancelled']) ? 'Late' : null),
                TextColumn::make('tasks_summary')
                    ->label('Tasks')
                    ->getStateUsing(fn($record) => $record->jobOrderTasks()->where('status', 'completed')->count() . ' / ' . $record->jobOrderTasks()->count())
                    ->badge()
                    ->color('gray'),
                TextColumn::make('materials_completion')
                    ->label('Materials Issued')
                    ->state(fn($record) => round($record->materialsCompletionPercentage(), 0) . '%')
                    ->badge()
                    ->color(fn($state) => match (true) {
                        (int)$state >= 100 => 'success',
                        (int)$state >= 50 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('total_price')
                    ->suffix(' birr')
                    ->visible(fn () => PanelAccess::canSeeMoneyValues())
                    ->sortable(),
                IconColumn::make('advance_paid')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->paymentAllocations()->exists())
                    ->label('Adv. Paid'),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\JobOrderExporter::class)
            ])
            ->filters([
                TernaryFilter::make('payment_status')
                    ->label('Payment Status')
                    ->placeholder('All')
                    ->trueLabel('Pending Payments')
                    ->falseLabel('Fully Paid')
                    ->queries(
                        true: fn($query) => $query->pendingPayment(),
                        false: fn($query) => $query->fullyPaid(),
                    ),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->preload()
                    ->searchable(),
                Filter::make('late_jobs')
                    ->label('Late Job Orders')
                    ->query(fn ($query) => $query->late())
                    ->toggle(),
            ])
            ->actions([
                EditAction::make(),
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
                            $result = $invoiceService->generateFromJobOrder($record);
                            
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
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\JobOrderExporter::class)
                ]),
            ]);
    }
}
