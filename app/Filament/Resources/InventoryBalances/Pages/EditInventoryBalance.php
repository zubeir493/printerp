<?php

namespace App\Filament\Resources\InventoryBalances\Pages;

use App\Filament\Resources\InventoryBalances\InventoryBalanceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInventoryBalance extends EditRecord
{
    protected static string $resource = InventoryBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
