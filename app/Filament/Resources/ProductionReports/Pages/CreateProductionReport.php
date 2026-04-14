<?php

namespace App\Filament\Resources\ProductionReports\Pages;

use App\Filament\Resources\ProductionReports\ProductionReportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductionReport extends CreateRecord
{
    protected static string $resource = ProductionReportResource::class;
}
