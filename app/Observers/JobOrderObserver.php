<?php

namespace App\Observers;

use App\Models\JobOrder;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class JobOrderObserver
{
    public function creating(JobOrder $jobOrder): void
    {
        if (!$jobOrder->job_order_number) {
            $lastJobOrder = JobOrder::orderBy('id', 'desc')->first();
            $lastNumber = 0;
            if ($lastJobOrder && preg_match('/JO-(\d+)/', $lastJobOrder->job_order_number, $matches)) {
                $lastNumber = (int) $matches[1];
            }
            $jobOrder->job_order_number = 'JO-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }
    }
}
