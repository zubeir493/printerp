<?php

namespace App\Observers;

use App\Models\JournalEntry;
use App\Models\Payment;
use App\Services\Accounting\CreatePaymentJournalEntry;

class PaymentObserver
{
    public function created(Payment $payment)
    {
        app(CreatePaymentJournalEntry::class)->handle($payment);
    }

    public function updating(Payment $payment)
    {
        $hasJournalEntries = JournalEntry::where('source_type', Payment::class)
            ->where('source_id', $payment->id)
            ->exists();

        if ($hasJournalEntries) {
            throw new \RuntimeException('Cannot edit payment after it has been posted to the accounting ledger.');
        }
    }
}
