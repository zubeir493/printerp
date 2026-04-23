<?php

namespace App\Filament\Resources\Employees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('image')
                    ->label('Photo')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) {
                            return new \Illuminate\Support\HtmlString('<img src="https://ui-avatars.com/api/?name=' . urlencode($record->full_name) . '&color=FFFFFF&background=020617" class="w-10 h-10 rounded-full" />');
                        }
                        $url = \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($state, now()->addMinutes(10));
                        return new \Illuminate\Support\HtmlString('<img src="' . $url . '" class="w-10 h-10 rounded-full object-cover" />');
                    })
                    ->width('50px')
                    ->html(),
                TextColumn::make('full_name')
                    ->label('Name')
                    ->description(fn($record) => $record->employee_id)
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('department')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'terminated' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('hire_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'terminated' => 'Terminated',
                    ]),
                SelectFilter::make('department'),
            ])
            ->actions([])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
