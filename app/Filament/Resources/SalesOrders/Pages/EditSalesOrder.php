<?php

namespace App\Filament\Resources\SalesOrders\Pages;

use App\Filament\Resources\SalesOrders\SalesOrderResource;
use App\Filament\Support\PanelAccess;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSalesOrder extends EditRecord
{
    protected static string $resource = SalesOrderResource::class;

    public static function canAccess($record = null): bool
    {
        return PanelAccess::canManageSalesOrders();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
