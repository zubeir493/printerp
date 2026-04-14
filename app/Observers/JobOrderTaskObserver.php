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

            $allTasks = $jobOrder->jobOrderTasks()->get();
            
            if ($allTasks->count() > 0) {
                $allFinished = $allTasks->every(fn($t) => in_array($t->status, ['completed', 'cancelled']));
                $hasCompleted = $allTasks->contains('status', 'completed');

                if ($allFinished) {
                    if ($hasCompleted) {
                        if ($jobOrder->status !== 'completed') {
                            $jobOrder->update(['status' => 'completed']);
                        }
                    } else { // All tasks are cancelled!
                        if ($jobOrder->status !== 'cancelled') {
                           $jobOrder->update(['status' => 'cancelled']);
                        }
                    }
                } elseif ($allTasks->contains('status', 'production')) {
                    if (in_array($jobOrder->status, ['draft', 'design'])) {
                        $jobOrder->update(['status' => 'production']);
                    }
                }
            }
        }
    }
}
