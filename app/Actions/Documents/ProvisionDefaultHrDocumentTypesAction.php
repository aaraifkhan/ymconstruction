<?php

namespace App\Actions\Documents;

use App\Enums\HrDocumentTypeCode;
use App\Models\Company;
use App\Models\HrDocumentType;

class ProvisionDefaultHrDocumentTypesAction
{
    /**
     * @return list<HrDocumentType>
     */
    public function handle(Company $company): array
    {
        return collect(HrDocumentTypeCode::cases())
            ->map(function (HrDocumentTypeCode $code) use ($company): HrDocumentType {
                $type = HrDocumentType::withTrashed()->firstOrCreate(
                    [
                        'company_id' => $company->getKey(),
                        'code' => $code,
                    ],
                    [
                        'name' => $code->label(),
                        'applicability' => $code->applicability(),
                        'default_classification' => $code->defaultClassification(),
                        'requires_issue_date' => false,
                        'requires_expiry' => false,
                        'requires_verification' => $code->requiresVerification(),
                        'requires_approval' => $code->requiresApproval(),
                        'is_required' => false,
                        'is_active' => true,
                    ],
                );

                if ($type->trashed()) {
                    $type->restore();
                }

                return $type;
            })
            ->all();
    }
}
