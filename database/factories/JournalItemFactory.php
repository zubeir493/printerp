<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalItemFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'journal_entry_id' => JournalEntry::factory(),
            'account_id' => Account::factory(),
            'debit' => fake()->randomFloat(2, 0, 9999999999999.99),
            'credit' => fake()->randomFloat(2, 0, 9999999999999.99),
        ];
    }
}
