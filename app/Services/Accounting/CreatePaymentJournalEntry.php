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

        $cashAccount = Account::getSystemAccount(Account::CODE_CASH, 'Cash', 'Asset');
        $arAccount = Account::getSystemAccount(Account::CODE_AR, 'Accounts Receivable', 'Asset');
        $apAccount = Account::getSystemAccount(Account::CODE_AP, 'Accounts Payable', 'Liability');

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
