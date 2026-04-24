<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(function ($state): string {
                        $roleValue = is_string($state) ? $state : ($state instanceof \App\UserRole ? $state->value : (string) $state);
                        return match ($roleValue) {
                            'admin' => 'danger',
                            'sales', 'retail' => 'success',
                            'finance', 'hr' => 'warning',
                            default => 'gray',
                        };
                    })
                    ->searchable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('role')
                    ->options([
                        \App\UserRole::Admin->value => 'Admin',
                        \App\UserRole::Operations->value => 'Operations',
                        \App\UserRole::Finance->value => 'Finance',
                        \App\UserRole::Sales->value => 'Sales',
                        \App\UserRole::Retail->value => 'Retail',
                        \App\UserRole::HR->value => 'HR',
                    ]),
            ])
            ->recordActions([
                Action::make('changePassword')
                    ->label('Change Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        TextInput::make('password')
                            ->password()
                            ->required()
                            ->revealable(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'password' => $data['password'], // Casts to hashed in model
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Password updated successfully')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
