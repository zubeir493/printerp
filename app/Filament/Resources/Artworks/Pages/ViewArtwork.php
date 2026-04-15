<?php

namespace App\Filament\Resources\Artworks\Pages;

use App\Filament\Resources\Artworks\ArtworkResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewArtwork extends ViewRecord
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
                    \Illuminate\Support\Facades\Mail::to($data['email'])
                        ->send(new \App\Mail\ShareArtwork($this->record, $data['email'], $data['message'] ?? null));

                    \Filament\Notifications\Notification::make()
                        ->title('Artwork Shared')
                        ->body("The artwork link has been sent to **{$data['email']}**.")
                        ->success()
                        ->send();
                }),
            EditAction::make(),
        ];
    }
}
