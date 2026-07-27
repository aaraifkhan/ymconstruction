<?php

namespace Database\Factories;

use App\Enums\ProcurementDocumentType;
use App\Models\Company;
use App\Models\ProcurementSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcurementSequence>
 */
class ProcurementSequenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'document_type' => ProcurementDocumentType::PurchaseRequisition,
            'calendar_year' => (int) now()->year,
            'prefix' => 'PR',
            'next_number' => 1,
            'padding' => 6,
            'is_active' => true,
        ];
    }
}
