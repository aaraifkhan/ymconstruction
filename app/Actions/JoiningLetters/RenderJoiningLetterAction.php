<?php

namespace App\Actions\JoiningLetters;

use App\Enums\JoiningLetterStatus;
use App\Models\JoiningLetter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RenderJoiningLetterAction
{
    public function handle(JoiningLetter $joiningLetter, User $actor, bool $authorize = true): JoiningLetter
    {
        return DB::transaction(function () use ($actor, $authorize, $joiningLetter): JoiningLetter {
            $lockedLetter = JoiningLetter::query()
                ->with([
                    'company',
                    'template',
                    'employment.employee',
                    'employment.department',
                    'employment.designation',
                    'employment.reportingEmployment.employee',
                ])
                ->whereKey($joiningLetter)
                ->lockForUpdate()
                ->firstOrFail();

            if ($authorize) {
                Gate::forUser($actor)->authorize('regenerate', $lockedLetter);
            }

            if (! in_array($lockedLetter->status, [JoiningLetterStatus::Draft, JoiningLetterStatus::Rejected], true)) {
                throw ValidationException::withMessages([
                    'joining_letter' => 'Only draft or rejected joining letters can be regenerated.',
                ]);
            }

            if (
                $lockedLetter->template === null
                || $lockedLetter->template->company_id !== $lockedLetter->company_id
                || ! $lockedLetter->template->is_active
            ) {
                throw ValidationException::withMessages([
                    'joining_letter_template_id' => 'Select an active template belonging to the current company.',
                ]);
            }

            if ($lockedLetter->employment->company_id !== $lockedLetter->company_id) {
                throw ValidationException::withMessages([
                    'employment_id' => 'The employment must belong to the joining-letter company.',
                ]);
            }

            $values = $this->placeholderValues($lockedLetter);
            $subject = $this->renderTemplate($lockedLetter->template->subject_template, $values);
            $body = $this->renderTemplate($lockedLetter->template->body_template, $values);

            $lockedLetter->update([
                'subject' => $subject,
                'body' => $body,
                'status' => JoiningLetterStatus::Draft,
                'submitted_by_id' => null,
                'submitted_at' => null,
                'approved_by_id' => null,
                'approved_at' => null,
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'content_checksum' => null,
            ]);

            activity('joining_letters')
                ->causedBy($actor)
                ->performedOn($lockedLetter)
                ->event('rendered')
                ->withProperties(['company_id' => $lockedLetter->company_id])
                ->log('rendered joining letter from template');

            return $lockedLetter;
        });
    }

    /**
     * @return array<string, string>
     */
    private function placeholderValues(JoiningLetter $letter): array
    {
        $employment = $letter->employment;
        $employee = $employment->employee;
        $workSchedule = collect([
            $employment->work_start_time && $employment->work_end_time
                ? "{$employment->work_start_time} to {$employment->work_end_time}"
                : null,
            $employment->working_days_per_week
                ? "{$employment->working_days_per_week} days per week"
                : null,
        ])->filter()->implode(', ');

        return [
            'company.name' => $letter->company->legal_name ?: $letter->company->name,
            'employee.full_name' => $employee->full_name,
            'employee.father_or_husband_name' => $employee->father_or_husband_name ?: 'Not specified',
            'employee.cnic' => $employee->cnic ?: 'Not specified',
            'employment.employee_code' => $employment->employee_code,
            'employment.designation' => $employment->designation?->name ?: 'Not assigned',
            'employment.department' => $employment->department?->name ?: 'Not assigned',
            'employment.joining_date' => $employment->joining_date->format('F j, Y'),
            'employment.reporting_manager' => $employment->reportingEmployment?->employee?->full_name ?: 'Not assigned',
            'employment.work_schedule' => $workSchedule ?: 'As per company policy',
            'letter.number' => $letter->letter_number,
            'letter.date' => $letter->letter_date->format('F j, Y'),
            'letter.effective_date' => $letter->employment_effective_date->format('F j, Y'),
            'letter.compensation' => $letter->formattedCompensation(),
        ];
    }

    /**
     * @param  array<string, string>  $values
     */
    private function renderTemplate(string $template, array $values): string
    {
        preg_match_all('/{{\s*([a-z_.]+)\s*}}/', $template, $matches);
        $unknownPlaceholders = collect($matches[1] ?? [])
            ->unique()
            ->reject(fn (string $placeholder): bool => array_key_exists($placeholder, $values));

        if ($unknownPlaceholders->isNotEmpty()) {
            throw ValidationException::withMessages([
                'body_template' => 'Unknown placeholders: '.$unknownPlaceholders->implode(', '),
            ]);
        }

        foreach ($values as $placeholder => $value) {
            $template = preg_replace(
                '/{{\s*'.preg_quote($placeholder, '/').'\s*}}/',
                Str::of($value)->squish()->toString(),
                $template,
            ) ?? $template;
        }

        return trim($template);
    }
}
