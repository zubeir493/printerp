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
            EditAction::make(),
        ];
    }
}
