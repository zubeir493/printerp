<?php

namespace App\Filament\Resources\PaymentAllocations\Pages;

use App\Filament\Resources\PaymentAllocations\PaymentAllocationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentAllocation extends EditRecord
{
    protected static string $resource = PaymentAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
