<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$jo = \App\Models\JobOrder::latest()->first();
if (!$jo) {
    echo "No job order found\n";
    exit;
}

echo "Current JO status: {$jo->status}\n";
foreach ($jo->jobOrderTasks as $task) {
    echo "Task {$task->id} status: {$task->status}\n";
}

echo "Updating tasks to completed...\n";
foreach ($jo->jobOrderTasks as $task) {
    $task->status = 'completed';
    $task->save();
}

$jo->refresh();
echo "New JO status: {$jo->status}\n";

$jo->update(['status' => 'draft']);
echo "Reset to draft. JO status: {$jo->status}\n";

echo "Updating tasks to cancelled...\n";
foreach ($jo->jobOrderTasks as $task) {
    $task->status = 'cancelled';
    $task->save();
}

$jo->refresh();
echo "New JO status after all cancelled: {$jo->status}\n";
