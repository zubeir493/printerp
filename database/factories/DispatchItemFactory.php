<?php

namespace Database\Factories;

use App\Models\Dispatch;
use App\Models\JobOrderTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class DispatchItemFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'dispatch_id' => Dispatch::factory(),
            'job_order_task_id' => JobOrderTask::factory(),
            'quantity' => fake()->numberBetween(-10000, 10000),
        ];
    }
}
