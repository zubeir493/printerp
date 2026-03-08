<?php

namespace App\Filament\Resources\JobOrders\Pages;

use App\Filament\Resources\JobOrders\JobOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJobOrders extends ListRecords
{
    protected static string $resource = JobOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
