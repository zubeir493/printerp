<?php

namespace App\Filament\Resources\JobOrderTasks\Pages;

use App\Filament\Resources\JobOrderTasks\JobOrderTaskResource;
use App\Filament\Support\PanelAccess;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJobOrderTask extends EditRecord
{
    protected static string $resource = JobOrderTaskResource::class;

    public static function canAccess($record = null): bool
    {
        return PanelAccess::canManageJobOrderTasks();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
