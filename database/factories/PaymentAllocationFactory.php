<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentAllocationFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'sales_invoice_id' => SalesInvoice::factory(),
            'purchase_invoice_id' => PurchaseInvoice::factory(),
            'allocated_amount' => fake()->randomFloat(2, 0, 9999999999999.99),
        ];
    }
}
