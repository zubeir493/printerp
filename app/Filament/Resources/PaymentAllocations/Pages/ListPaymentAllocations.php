<?php

namespace App\Filament\Resources\PaymentAllocations\Pages;

use App\Filament\Resources\PaymentAllocations\PaymentAllocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentAllocations extends ListRecords
{
    protected static string $resource = PaymentAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
