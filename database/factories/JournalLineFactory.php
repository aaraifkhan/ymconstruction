<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalLine>
 */
class JournalLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'line_number' => 1,
            'debit' => 1,
            'credit' => 0,
        ];
    }

    public function forEntryAndAccount(JournalEntry $entry, Account $account): static
    {
        return $this->state(fn (): array => [
            'journal_entry_id' => $entry->getKey(),
            'company_id' => $entry->company_id,
            'account_id' => $account->getKey(),
        ]);
    }
}
