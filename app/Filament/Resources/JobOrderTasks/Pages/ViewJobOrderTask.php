<?php

namespace App\Filament\Resources\JobOrderTasks\Pages;

use App\Filament\Resources\JobOrderTasks\JobOrderTaskResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewJobOrderTask extends ViewRecord
{
    protected static string $resource = JobOrderTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
