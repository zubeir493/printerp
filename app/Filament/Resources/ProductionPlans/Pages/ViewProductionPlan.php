<?php
 
 namespace App\Filament\Resources\ProductionPlans\Pages;
 
 use App\Filament\Resources\ProductionPlans\ProductionPlanResource;
 use Filament\Actions\EditAction;
 use Filament\Resources\Pages\ViewRecord;
 
 class ViewProductionPlan extends ViewRecord
 {
     protected static string $resource = ProductionPlanResource::class;
 
     protected function getHeaderActions(): array
     {
         return [
             \Filament\Actions\Action::make('report_week')
                 ->label('Report Week')
                 ->icon('heroicon-o-clipboard-document-check')
                 ->color('success')
                 ->visible(fn($record) => $record->status === 'approved' && !\App\Models\ProductionReport::where('production_plan_id', $record->id)->exists())
                 ->action(function ($record) {
                     $report = \App\Models\ProductionReport::create([
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

                     \Filament\Notifications\Notification::make()
                        ->title('Production report generated')
                        ->success()
                        ->send();

                     return redirect(\App\Filament\Resources\ProductionReports\ProductionReportResource::getUrl('edit', ['record' => $report]));
                 }),
             EditAction::make(),
         ];
     }
 }
 
