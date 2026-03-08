<?php

namespace App\Services\Accounting;

use App\Models\SalesOrder;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class CreateSalesJournalEntry
{
    public function handle(SalesOrder $sale)
    {
        $sale->load('salesOrderItems');

        $total = $sale->salesOrderItems->sum(function ($item) {
            return (float)$item->quantity * (float)$item->unit_price;
        });

        if ($total <= 0) {
            $total = (float)$sale->total;
        }

        if ($total <= 0) return;

        $cashAccount = Account::where('name', 'Cash')->orWhere('name', 'Cash in Hand')->first() ?? Account::firstOrCreate(
            ['name' => 'Cash'],
            ['type' => 'Asset', 'code' => 'ACC-CASH']
        );

        $revenueAccount = Account::where('name', 'Sales Revenue')->first() ?? Account::firstOrCreate(
            ['name' => 'Sales Revenue'],
            ['type' => 'Revenue', 'code' => 'ACC-REV']
        );

        $journalEntry = JournalEntry::create([
            'date' => $sale->order_date ?? now(),
            'reference' => 'Sale #' . $sale->order_number,
            'source_type' => SalesOrder::class,
            'source_id' => $sale->id,
            'narration' => 'Sale #' . $sale->order_number,
            'total_debit' => $total,
            'total_credit' => $total,
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        JournalItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $cashAccount->id,
            'debit' => $total,
            'credit' => 0,
        ]);

        JournalItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $revenueAccount->id,
            'debit' => 0,
            'credit' => $total,
        ]);
    }
}
