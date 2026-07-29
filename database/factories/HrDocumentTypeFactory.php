<?php

namespace Database\Factories;

use App\Enums\DocumentClassification;
use App\Enums\HrDocumentApplicability;
use App\Enums\HrDocumentTypeCode;
use App\Models\Company;
use App\Models\HrDocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrDocumentType>
 */
class HrDocumentTypeFactory extends Factory
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
            'code' => HrDocumentTypeCode::EducationalDocument,
            'name' => HrDocumentTypeCode::EducationalDocument->label(),
            'applicability' => HrDocumentApplicability::Employee,
            'default_classification' => DocumentClassification::Restricted,
            'requires_issue_date' => false,
            'requires_expiry' => false,
            'requires_verification' => true,
            'requires_approval' => false,
            'is_required' => false,
            'is_active' => true,
        ];
    }
}
