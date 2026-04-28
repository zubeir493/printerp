<?php

namespace App\Filament\Resources\JobOrderTasks\RelationManagers;

use App\Services\MaterialIssueService;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class MaterialRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'materialRequests';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('inventory_item_id')
                    ->relationship('inventoryItem', 'name')
                    ->required(),
                \Filament\Forms\Components\TextInput::make('required_quantity')
                    ->label('Required Quantity')
                    ->numeric()
                    ->required()
                    ->default(function () {
                        // Auto-set from JobOrderTask quantity
                        $owner = $this->getOwnerRecord();
                        return $owner ? $owner->quantity : 0;
                    })
                    ->helperText('This is the quantity required for the task'),
                \Filament\Forms\Components\TextInput::make('requested_quantity')
                    ->label('Requested Quantity')
                    ->numeric()
                    ->required()
                    ->default(0),
                \Filament\Forms\Components\TextInput::make('issued_quantity')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false)
                    ->default(0),
                \Filament\Forms\Components\Textarea::make('reason')
                    ->label('Reason for Request')
                    ->placeholder('Please explain why this material is needed...')
                    ->required()
                    ->helperText('Provide a clear reason for this material request')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('inventoryItem.name')->label('Material'),
                Tables\Columns\TextColumn::make('required_quantity')->label('Required'),
                Tables\Columns\TextColumn::make('requested_quantity')->label('Requested'),
                Tables\Columns\TextColumn::make('issued_quantity')->label('Issued'),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        return $column->getState();
                    }),
                Tables\Columns\TextColumn::make('pendingIssueApprovals_count')
                    ->label('Pending Approvals')
                    ->counts('pendingIssueApprovals'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                \Filament\Actions\Action::make('issue')
                    ->label('Issue')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('success')
                    ->visible(fn ($record) => $record->requested_quantity > $record->issued_quantity && !$record->pendingIssueApprovals()->exists())
                    ->form([
                        \Filament\Forms\Components\Select::make('warehouse_id')
                            ->label('Warehouse')
                            ->options(\App\Models\Warehouse::pluck('name', 'id'))
                            ->default(fn () => \App\Models\Warehouse::where('is_default', true)->value('id'))
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('quantity')
                            ->label('Quantity to Issue')
                            ->numeric()
                            ->required()
                            ->default(fn ($record) => $record->requested_quantity - $record->issued_quantity)
                            ->maxValue(fn ($record) => $record->requested_quantity - $record->issued_quantity)
                            ->helperText('If this exceeds the required quantity, it will be queued for approval instead of issuing immediately.'),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $result = app(MaterialIssueService::class)->issue($record, (int) $data['warehouse_id'], (float) $data['quantity'], auth()->user());

                            \Filament\Notifications\Notification::make()
                                ->title($result['status'] === 'pending_approval' ? 'Over-issue sent for approval' : 'Materials issued successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error issuing materials')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
