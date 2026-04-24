<?php

namespace App\Filament\Resources\MaterialIssueApprovals\Pages;

use App\Filament\Resources\MaterialIssueApprovals\MaterialIssueApprovalResource;
use Filament\Resources\Pages\ManageRecords;

class ManageMaterialIssueApprovals extends ManageRecords
{
    protected static string $resource = MaterialIssueApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
