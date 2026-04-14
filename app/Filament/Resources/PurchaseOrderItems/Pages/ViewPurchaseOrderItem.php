<?php

namespace App\Filament\Resources\PurchaseOrderItems\Pages;

use App\Filament\Resources\PurchaseOrderItems\PurchaseOrderItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrderItem extends ViewRecord
{
    protected static string $resource = PurchaseOrderItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
