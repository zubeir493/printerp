<?php

namespace Database\Factories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'job_order_number' => fake()->word(),
            'partner_id' => Partner::factory(),
            'job_type' => fake()->randomElement(["books","packages"]),
            'submission_date' => fake()->date(),
            'remarks' => fake()->text(),
            'total_price' => fake()->randomFloat(2, 0, 9999999999.99),
            'status' => fake()->randomElement(["draft","design","production","completed","cancelled"]),
        ];
    }
}
