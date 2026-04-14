<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    public static function canViewAny(): bool
    {
        return \Illuminate\Support\Facades\Auth::user()?->role === \App\UserRole::Admin;
    }

    public static function canDelete($record): bool
    {
        return \Illuminate\Support\Facades\Auth::id() !== $record?->id;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }



    public static function table(Table $table): Table
    {
        return UsersTable::configure($table)
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => \App\Filament\Resources\Users\Pages\ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
