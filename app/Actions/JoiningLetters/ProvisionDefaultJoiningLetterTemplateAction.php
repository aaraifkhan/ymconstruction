<?php

namespace App\Actions\JoiningLetters;

use App\Models\Company;
use App\Models\JoiningLetterTemplate;

class ProvisionDefaultJoiningLetterTemplateAction
{
    public function handle(Company $company): JoiningLetterTemplate
    {
        return $company->joiningLetterTemplates()->firstOrCreate(
            ['code' => 'standard-joining-letter'],
            [
                'name' => 'Standard Joining Letter',
                'subject_template' => 'JOINING LETTER — {{ employee.full_name }}',
                'body_template' => self::bodyTemplate(),
                'is_default' => true,
                'is_active' => true,
            ],
        );
    }

    public static function bodyTemplate(): string
    {
        return <<<'TEXT'
DATE: {{ letter.date }}
REFERENCE: {{ letter.number }}

Dear {{ employee.full_name }},

We are pleased to confirm your appointment for the position of {{ employment.designation }} at {{ company.name }}.

Employee code: {{ employment.employee_code }}
Father's / Husband's name: {{ employee.father_or_husband_name }}
CNIC: {{ employee.cnic }}
Department: {{ employment.department }}

Your employment is effective from {{ letter.effective_date }}. You will report to {{ employment.reporting_manager }}.

Your compensation package is {{ letter.compensation }}.
Your work schedule is {{ employment.work_schedule }}.

You are expected to follow all company policies and procedures, maintain confidentiality, and work diligently toward company goals.

We congratulate you and look forward to a mutually beneficial working relationship.

Sincerely,
Authorized Signatory
{{ company.name }}

Acknowledged and accepted by:
{{ employee.full_name }}
TEXT;
    }
}
