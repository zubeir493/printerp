<?php

namespace Database\Factories;

use App\Models\JobOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class DispatchFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'job_order_id' => JobOrder::factory(),
            'delivery_date' => fake()->date(),
            'remarks' => fake()->text(),
        ];
    }
}
