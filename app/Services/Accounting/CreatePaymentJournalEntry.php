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

        $defaultCashAccount = Account::getSystemAccount(Account::CODE_CASH, 'Cash', 'Asset');
        $arAccount = Account::getSystemAccount(Account::CODE_AR, 'Accounts Receivable', 'Asset');
        $apAccount = Account::getSystemAccount(Account::CODE_AP, 'Accounts Payable', 'Liability');

        $journalEntry = JournalEntry::create([
            'date' => $payment->payment_date ?? now(),
            'reference' => 'Payment #' . $payment->payment_number,
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'narration' => $payment->reference ?? 'Payment #' . $payment->payment_number . ($payment->payment_type !== 'standard' ? " ({$payment->payment_type})" : ""),
            'total_debit' => $amount,
            'total_credit' => $amount,
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        if ($payment->payment_type === 'standard') {
            if ($payment->direction === 'inbound') {
                // Debit Cash, Credit AR
                $this->createItems($journalEntry->id, $defaultCashAccount->id, $arAccount->id, $amount);
            } else {
                // Debit AP, Credit Cash
                $this->createItems($journalEntry->id, $apAccount->id, $defaultCashAccount->id, $amount);
            }
        } elseif ($payment->payment_type === 'expense') {
            // Debit the selected Account (Expense), Credit Default Cash
            if ($payment->direction === 'outbound') {
                $this->createItems($journalEntry->id, $payment->account_id, $defaultCashAccount->id, $amount);
            } else {
                // Inbound expense? (Maybe a refund) - Debit Cash, Credit Expense
                $this->createItems($journalEntry->id, $defaultCashAccount->id, $payment->account_id, $amount);
            }
        } elseif ($payment->payment_type === 'petty_cash') {
            // Assuming the selected account_id IS the Petty Cash account (Asset)
            // If outbound: Credit Petty Cash, Debit Miscellaneous Expense (or similar)
            $miscExpense = Account::getSystemAccount('5000', 'Miscellaneous Expense', 'Expense');
            
            if ($payment->direction === 'outbound') {
                $this->createItems($journalEntry->id, $miscExpense->id, $payment->account_id, $amount);
            } else {
                // Topping up petty cash: Debit Petty Cash, Credit main Cash
                $this->createItems($journalEntry->id, $payment->account_id, $defaultCashAccount->id, $amount);
            }
        }
    }

    private function createItems($entryId, $debitAccountId, $creditAccountId, $amount)
    {
        JournalItem::create([
            'journal_entry_id' => $entryId,
            'account_id' => $debitAccountId,
            'debit' => $amount,
            'credit' => 0,
        ]);
        JournalItem::create([
            'journal_entry_id' => $entryId,
            'account_id' => $creditAccountId,
            'debit' => 0,
            'credit' => $amount,
        ]);
    }
}
