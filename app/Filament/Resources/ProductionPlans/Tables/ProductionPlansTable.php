<?php

namespace App\Filament\Resources\ProductionPlans\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use App\Models\ProductionReport;
use App\Models\ProductionReportItem;
use Filament\Actions\Action as ActionsAction;
use Filament\Actions\BulkActionGroup as ActionsBulkActionGroup;
use Filament\Actions\EditAction as ActionsEditAction;

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
                ActionsEditAction::make(),
                ActionsAction::make('report_week')
                    ->label('Report Week')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'approved' && !ProductionReport::where('production_plan_id', $record->id)->exists())
                    ->action(function ($record) {
                        $report = ProductionReport::create([
                            'production_plan_id' => $record->id,
                            'status' => 'draft',
                        ]);

                        foreach ($record->machines as $planMachine) {
                            $reportMachine = $report->machines()->create([
                                'production_plan_machine_id' => $planMachine->id,
                            ]);

                            foreach ($planMachine->items as $item) {
                                $reportMachine->items()->create([
                                    'production_plan_item_id' => $item->id,
                                    'date' => now(),
                                    'actual_quantity' => $item->planned_quantity,
                                    'plates_used' => $item->planned_plates,
                                    'rounds' => $item->planned_rounds,
                                ]);
                            }
                        }

                        return redirect(\App\Filament\Resources\ProductionReports\ProductionReportResource::getUrl('edit', ['record' => $report]));
                    }),
            ])
            ->recordActions([
                ActionsEditAction::make(),
            ])
            ->bulkActions([]);
    }
}
