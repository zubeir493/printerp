<?php

namespace App\Services\Accounting;

use App\Models\PurchaseOrder;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class CreatePurchaseOrderJournalEntry
{
    public function handle(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('purchaseOrderItems');

        $total = $purchaseOrder->purchaseOrderItems->sum(function ($item) {
            return (float)$item->quantity * (float)$item->unit_price;
        });

        if ($total <= 0) {
            $total = (float)$purchaseOrder->subtotal;
        }

        if ($total <= 0) return;

        $inventoryAccount = Account::where('name', 'Inventory')->first() ?? Account::firstOrCreate(
            ['name' => 'Inventory'],
            ['type' => 'Asset', 'code' => 'ACC-INV']
        );
        
        $accountsPayableAccount = Account::where('name', 'like', 'Accounts Payable%')->first() ?? Account::firstOrCreate(
            ['name' => 'Accounts Payable'],
            ['type' => 'Liability', 'code' => 'ACC-AP']
        );

        $journalEntry = JournalEntry::create([
            'date' => $purchaseOrder->order_date ?? now(),
            'reference' => 'Purchase Order #' . $purchaseOrder->po_number,
            'source_type' => PurchaseOrder::class,
            'source_id' => $purchaseOrder->id,
            'narration' => 'Purchase Order #' . $purchaseOrder->po_number,
            'total_debit' => $total,
            'total_credit' => $total,
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        JournalItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $inventoryAccount->id,
            'debit' => $total,
            'credit' => 0,
        ]);

        JournalItem::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $accountsPayableAccount->id,
            'debit' => 0,
            'credit' => $total,
        ]);
    }
}
