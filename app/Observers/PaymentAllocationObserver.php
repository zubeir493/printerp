<?php

namespace App\Observers;

use App\Models\PaymentAllocation;
use App\Models\JobOrder;

class PaymentAllocationObserver
{
    /**
     * Handle the PaymentAllocation "saved" event.
     */
    public function saved(PaymentAllocation $paymentAllocation): void
    {
        $this->updateAllocatable($paymentAllocation);
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
