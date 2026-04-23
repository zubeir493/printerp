<?php

namespace App\Observers;

use App\Models\JobOrderTask;

class JobOrderTaskObserver
{
    public function saved(JobOrderTask $task): void
    {
        $this->syncJobOrderStatus($task->jobOrder);
    }

    public function deleted(JobOrderTask $task): void
    {
        $this->syncJobOrderStatus($task->jobOrder);
    }

    private function syncJobOrderStatus(?\App\Models\JobOrder $jobOrder): void
    {
        if ($jobOrder) {
            $jobOrder->recalculateTotal();
        }
    }
}
