<?php

namespace App\Filament\Resources\ProductionPlans\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Table;
use App\Models\ProductionReport;
use App\Models\ProductionReportItem;

class ProductionPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('week_start')
                    ->date()
                    ->sortable(),
                TextColumn::make('week_end')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'approved' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'approved' => 'Approved',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                Action::make('report_week')
                    ->label('Report Week')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'approved' && !ProductionReport::where('production_plan_id', $record->id)->exists())
                    ->action(function ($record) {
                        $report = ProductionReport::create([
                            'production_plan_id' => $record->id,
                            'status' => 'draft',
                        ]);

                        foreach ($record->items as $item) {
                            ProductionReportItem::create([
                                'production_report_id' => $report->id,
                                'production_plan_item_id' => $item->id,
                                'date' => now(),
                                'actual_quantity' => $item->planned_quantity,
                                'plates_used' => $item->planned_plates,
                                'rounds' => $item->planned_rounds,
                            ]);
                        }

                        return redirect(\App\Filament\Resources\ProductionReports\ProductionReportResource::getUrl('edit', ['record' => $report]));
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
