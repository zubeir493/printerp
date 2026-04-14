<?php

namespace App\Filament\Resources\JobOrderTasks\Pages;

use App\Filament\Resources\JobOrderTasks\JobOrderTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJobOrderTask extends CreateRecord
{
    protected static string $resource = JobOrderTaskResource::class;
}
