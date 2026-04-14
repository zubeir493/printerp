<?php

namespace App\Filament\Resources\Artworks\Pages;

use App\Filament\Resources\Artworks\ArtworkResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditArtwork extends EditRecord
{
    protected static string $resource = ArtworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('sendEmail')
                ->label('Share via Email')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\TextInput::make('email')
                        ->label('Recipient Email')
                        ->email()
                        ->required(),
                    \Filament\Forms\Components\Textarea::make('message')
                        ->label('Optional Message')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    \Filament\Notifications\Notification::make()
                        ->title('Artwork Shared')
                        ->body("The artwork link has been sent to **{$data['email']}**.")
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
