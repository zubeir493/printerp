<?php

namespace App\Filament\Resources\JobOrderTasks\Pages;

use App\Filament\Resources\JobOrderTasks\JobOrderTaskResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJobOrderTask extends EditRecord
{
    protected static string $resource = JobOrderTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
