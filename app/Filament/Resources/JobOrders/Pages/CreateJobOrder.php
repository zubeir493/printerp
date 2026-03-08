<?php

namespace App\Filament\Resources\JobOrders\Pages;

use App\Filament\Resources\JobOrders\JobOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJobOrder extends CreateRecord
{
    protected static string $resource = JobOrderResource::class;
}
