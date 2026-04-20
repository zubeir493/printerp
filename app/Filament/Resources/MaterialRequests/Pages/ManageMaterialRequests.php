<?php

namespace App\Filament\Resources\MaterialRequests\Pages;

use App\Filament\Resources\MaterialRequests\MaterialRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMaterialRequests extends ManageRecords
{
    protected static string $resource = MaterialRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
