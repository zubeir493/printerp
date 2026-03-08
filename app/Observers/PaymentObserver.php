<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\Accounting\CreatePaymentJournalEntry;

class PaymentObserver
{
    public function created(Payment $payment)
    {
        app(CreatePaymentJournalEntry::class)->handle($payment);
    }
}
