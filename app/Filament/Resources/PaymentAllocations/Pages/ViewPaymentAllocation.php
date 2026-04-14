<?php

namespace App\Filament\Resources\PaymentAllocations\Pages;

use App\Filament\Resources\PaymentAllocations\PaymentAllocationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentAllocation extends ViewRecord
{
    protected static string $resource = PaymentAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
