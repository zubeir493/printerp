<?php

namespace App\Observers;

use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\JobOrder;

class PaymentAllocationObserver
{
    /**
     * Handle the PaymentAllocation "saving" event.
     */
    public function saving(PaymentAllocation $paymentAllocation): void
    {
        if ($paymentAllocation->payment_id) {
            $hasJournalEntries = JournalEntry::where('source_type', Payment::class)
                ->where('source_id', $paymentAllocation->payment_id)
                ->exists();

            if ($hasJournalEntries) {
                throw new \RuntimeException('Cannot edit payment allocation after its payment has been posted to the accounting ledger.');
            }
        }
    }

    /**
     * Handle the PaymentAllocation "deleted" event.
     */
    public function deleted(PaymentAllocation $paymentAllocation): void
    {
        $this->updateAllocatable($paymentAllocation);
    }

    /**
     * Update the parent allocatable based on allocations.
     */
    protected function updateAllocatable(PaymentAllocation $paymentAllocation): void
    {
        if ($paymentAllocation->allocatable_type === JobOrder::class) {
            $jobOrder = $paymentAllocation->allocatable;
            if ($jobOrder) {
                $firstAllocation = $jobOrder->paymentAllocations()->orderBy('id')->first();
                
                $jobOrder->updateQuietly([
                    'advance_paid' => $firstAllocation !== null,
                    'advance_amount' => $firstAllocation ? $firstAllocation->allocated_amount : 0,
                ]);
            }
        }
    }
}
