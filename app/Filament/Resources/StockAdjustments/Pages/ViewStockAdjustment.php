<?php
 
 namespace App\Filament\Resources\StockAdjustments\Pages;
 
 use App\Filament\Resources\StockAdjustments\StockAdjustmentResource;
 use Filament\Actions\EditAction;
 use Filament\Resources\Pages\ViewRecord;
 
 class ViewStockAdjustment extends ViewRecord
 {
     protected static string $resource = StockAdjustmentResource::class;
 
     protected function getHeaderActions(): array
     {
         return [
             EditAction::make(),
             \Filament\Actions\Action::make('post')
                ->label('Post Adjustment')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status === 'draft')
                ->action(function ($record) {
                    $record->post();
                    \Filament\Notifications\Notification::make()
                        ->title('Adjustment Posted Successfully')
                        ->success()
                        ->send();
                }),
         ];
     }
 }
 
