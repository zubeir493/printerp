<?php

namespace App\Policies;

use App\Models\PaymentAllocation;
use App\Models\User;
use App\Models\JournalEntry;
use App\Models\Payment;

class PaymentAllocationPolicy
{
    public function update(User $user, PaymentAllocation $paymentAllocation): bool
    {
        // Allow editing only if the related payment has no journal entries
        return !JournalEntry::where('source_type', Payment::class)
            ->where('source_id', $paymentAllocation->payment_id)
            ->exists();
    }

    public function delete(User $user, PaymentAllocation $paymentAllocation): bool
    {
        // Same logic for deletion
        return !JournalEntry::where('source_type', Payment::class)
            ->where('source_id', $paymentAllocation->payment_id)
            ->exists();
    }
}