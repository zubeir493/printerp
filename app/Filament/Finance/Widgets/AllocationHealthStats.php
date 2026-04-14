<?php

namespace App\Filament\Finance\Widgets;

use App\Models\Payment;
use App\Models\JournalEntry;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class AllocationHealthStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1. Unallocated Funds
        // Difference between total payments and total allocations
        $totalPayments = \App\Models\Payment::sum('amount');
        $totalAllocations = \App\Models\PaymentAllocation::sum('allocated_amount');
        $unallocatedFunds = max(0, $totalPayments - $totalAllocations);
        
        // 2. Pending Reconciliations (Draft Journal Entries)
        $pendingJournals = \App\Models\JournalEntry::where('status', 'draft')->count();

        return [
            Stat::make('Unallocated Funds', number_format($unallocatedFunds, 2) . ' Birr')
                ->description('Payments not yet linked to orders')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($unallocatedFunds > 1000 ? 'danger' : 'success'),
            
            Stat::make('Journal Drafts', $pendingJournals)
                ->description('Awaiting posting/review')
                ->descriptionIcon('heroicon-m-document-text')
                ->color($pendingJournals > 5 ? 'warning' : 'success'),
                
            Stat::make('Total Payments Recv.', number_format($totalPayments, 2) . ' Birr')
                ->description('Life-time aggregate')
                ->color('primary'),
        ];
    }
}
