<?php

namespace App\Models;

use Database\Factories\JoiningLetterTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id',
    'name',
    'code',
    'subject_template',
    'body_template',
    'is_default',
    'is_active',
])]
class JoiningLetterTemplate extends Model
{
    /** @use HasFactory<JoiningLetterTemplateFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'is_default' => false,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saved(function (JoiningLetterTemplate $template): void {
            if (! $template->is_default) {
                return;
            }

            self::query()
                ->whereBelongsTo($template->company)
                ->whereKeyNot($template)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function joiningLetters(): HasMany
    {
        return $this->hasMany(JoiningLetter::class);
    }

    /**
     * @return array<string, string>
     */
    public static function placeholderLabels(): array
    {
        return [
            'company.name' => 'Company name',
            'employee.full_name' => 'Employee full name',
            'employee.father_or_husband_name' => "Father's / husband's name",
            'employee.cnic' => 'Employee CNIC',
            'employment.employee_code' => 'Employee code',
            'employment.designation' => 'Designation',
            'employment.department' => 'Department',
            'employment.joining_date' => 'Joining date',
            'employment.reporting_manager' => 'Reporting manager',
            'employment.work_schedule' => 'Work schedule',
            'letter.number' => 'Letter number',
            'letter.date' => 'Letter date',
            'letter.effective_date' => 'Employment effective date',
            'letter.compensation' => 'Compensation snapshot',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('joining_letter_templates')
            ->logOnly([
                'company_id',
                'name',
                'code',
                'subject_template',
                'body_template',
                'is_default',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
