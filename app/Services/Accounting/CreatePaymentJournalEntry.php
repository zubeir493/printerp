<?php

namespace App\Services\Accounting;

use App\Models\Payment;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class CreatePaymentJournalEntry
{
    public function handle(Payment $payment)
    {
        $amount = (float)$payment->amount;
        if ($amount <= 0) return;

        $cashAccount = Account::firstOrCreate(
            ['name' => 'Cash'],
            ['type' => 'Asset', 'code' => 'ACC-CASH']
        );

        $arAccount = Account::firstOrCreate(
            ['name' => 'Accounts Receivable'],
            ['type' => 'Asset', 'code' => 'ACC-AR']
        );

        $apAccount = Account::firstOrCreate(
            ['name' => 'Accounts Payable'],
            ['type' => 'Liability', 'code' => 'ACC-AP']
        );

        $journalEntry = JournalEntry::create([
            'date' => $payment->payment_date ?? now(),
            'reference' => 'Payment #' . $payment->payment_number,
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'narration' => $payment->reference ?? 'Payment #' . $payment->payment_number,
            'total_debit' => $amount,
            'total_credit' => $amount,
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        if ($payment->direction === 'inbound') {
            // Debit Cash, Credit AR
            JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $cashAccount->id,
                'debit' => $amount,
                'credit' => 0,
            ]);
            JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $arAccount->id,
                'debit' => 0,
                'credit' => $amount,
            ]);
        } else {
            // Debit AP, Credit Cash
            JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $apAccount->id,
                'debit' => $amount,
                'credit' => 0,
            ]);
            JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $cashAccount->id,
                'debit' => 0,
                'credit' => $amount,
            ]);
        }
    }
}
