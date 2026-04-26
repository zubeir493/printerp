<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\SalesOrder;

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

        $existingEntry = JournalEntry::query()
            ->where('source_type', SalesOrder::class)
            ->where('source_id', $sale->id)
            ->first();

        if ($existingEntry) {
            return;
        }

        $receivablesAccount = Account::getSystemAccount(Account::CODE_AR, 'Accounts Receivable', 'Asset');

        $revenueAccount = Account::firstOrCreate(
            ['code' => '4000'],
            ['name' => 'Sales Revenue', 'type' => 'Revenue']
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
            'account_id' => $receivablesAccount->id,
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
