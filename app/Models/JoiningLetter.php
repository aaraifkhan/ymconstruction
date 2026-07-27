<?php

namespace App\Models;

use App\Enums\JoiningLetterStatus;
use Database\Factories\JoiningLetterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id',
    'employment_id',
    'joining_letter_template_id',
    'letter_number',
    'status',
    'subject',
    'body',
    'compensation_amount',
    'currency_code',
    'letter_date',
    'employment_effective_date',
    'created_by_id',
    'submitted_by_id',
    'submitted_at',
    'approved_by_id',
    'approved_at',
    'rejected_by_id',
    'rejected_at',
    'rejection_reason',
    'issued_by_id',
    'issued_at',
    'accepted_by_name',
    'accepted_at',
    'acceptance_notes',
    'content_checksum',
])]
#[Hidden(['body', 'compensation_amount'])]
class JoiningLetter extends Model
{
    /** @use HasFactory<JoiningLetterFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'status' => JoiningLetterStatus::Draft->value,
        'currency_code' => 'PKR',
    ];

    protected static function booted(): void
    {
        static::saving(function (JoiningLetter $letter): void {
            $employmentBelongsToCompany = Employment::query()
                ->whereKey($letter->employment_id)
                ->where('company_id', $letter->company_id)
                ->exists();

            if (! $employmentBelongsToCompany) {
                throw ValidationException::withMessages([
                    'employment_id' => 'The employment must belong to the joining-letter company.',
                ]);
            }

            $templateBelongsToCompany = JoiningLetterTemplate::query()
                ->whereKey($letter->joining_letter_template_id)
                ->where('company_id', $letter->company_id)
                ->exists();

            if (! $templateBelongsToCompany) {
                throw ValidationException::withMessages([
                    'joining_letter_template_id' => 'The template must belong to the joining-letter company.',
                ]);
            }
        });

        static::updating(function (JoiningLetter $letter): void {
            $originalStatus = JoiningLetterStatus::from($letter->getRawOriginal('status'));

            if ($originalStatus === JoiningLetterStatus::Accepted) {
                throw ValidationException::withMessages([
                    'joining_letter' => 'An accepted joining letter is immutable.',
                ]);
            }

            if (
                $originalStatus === JoiningLetterStatus::Issued
                && $letter->isDirty(array_diff($letter->getFillable(), [
                    'status',
                    'accepted_by_name',
                    'accepted_at',
                    'acceptance_notes',
                ]))
            ) {
                throw ValidationException::withMessages([
                    'joining_letter' => 'Issued joining-letter content is immutable.',
                ]);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(JoiningLetterTemplate::class, 'joining_letter_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }

    public function formattedCompensation(): string
    {
        if ($this->compensation_amount === null) {
            return 'Not specified';
        }

        return $this->currency_code.' '.number_format((float) $this->compensation_amount, 2);
    }

    public function bodyForDisplay(bool $canViewCompensation): string
    {
        if ($canViewCompensation || $this->compensation_amount === null) {
            return $this->body;
        }

        return Str::replace(
            $this->formattedCompensation(),
            '[Compensation hidden]',
            $this->body,
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('joining_letters')
            ->logOnly([
                'company_id',
                'employment_id',
                'joining_letter_template_id',
                'letter_number',
                'status',
                'subject',
                'currency_code',
                'letter_date',
                'employment_effective_date',
                'created_by_id',
                'submitted_by_id',
                'submitted_at',
                'approved_by_id',
                'approved_at',
                'rejected_by_id',
                'rejected_at',
                'rejection_reason',
                'issued_by_id',
                'issued_at',
                'accepted_by_name',
                'accepted_at',
                'acceptance_notes',
                'content_checksum',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'status' => JoiningLetterStatus::class,
            'body' => 'encrypted',
            'compensation_amount' => 'encrypted',
            'letter_date' => 'date',
            'employment_effective_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'issued_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }
}
