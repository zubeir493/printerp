<?php

namespace App\Filament\Resources\JobOrderTasks\Pages;

use App\Filament\Resources\JobOrderTasks\JobOrderTaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJobOrderTasks extends ListRecords
{
    protected static string $resource = JobOrderTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
