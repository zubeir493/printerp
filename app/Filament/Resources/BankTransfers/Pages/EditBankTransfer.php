<?php

namespace App\Filament\Resources\BankTransfers\Pages;

use App\Filament\Resources\BankTransfers\BankTransferResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBankTransfer extends EditRecord
{
    protected static string $resource = BankTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
