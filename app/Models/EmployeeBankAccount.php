<?php

namespace App\Models;

use Database\Factories\EmployeeBankAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'employee_id',
    'bank_name',
    'branch_name',
    'branch_code',
    'account_title',
    'account_number',
    'iban',
    'is_primary_for_payroll',
    'is_active',
    'notes',
])]
#[Hidden(['account_number', 'iban', 'iban_hash'])]
class EmployeeBankAccount extends Model
{
    /** @use HasFactory<EmployeeBankAccountFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'is_primary_for_payroll' => false,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (EmployeeBankAccount $bankAccount): void {
            if (! $bankAccount->isDirty('iban')) {
                return;
            }

            $iban = $bankAccount->iban;
            $bankAccount->iban_hash = $iban === null
                ? null
                : hash_hmac('sha256', strtoupper(str_replace(' ', '', $iban)), config('app.key'));
        });

        static::saved(function (EmployeeBankAccount $bankAccount): void {
            if (! $bankAccount->is_primary_for_payroll) {
                return;
            }

            self::query()
                ->whereBelongsTo($bankAccount->employee)
                ->whereKeyNot($bankAccount)
                ->where('is_primary_for_payroll', true)
                ->update(['is_primary_for_payroll' => false]);
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function maskedAccountNumber(): ?string
    {
        return $this->maskSensitiveValue($this->account_number);
    }

    public function maskedIban(): ?string
    {
        return $this->maskSensitiveValue($this->iban);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employee_bank_accounts')
            ->logOnly([
                'employee_id',
                'bank_name',
                'branch_name',
                'branch_code',
                'account_title',
                'is_primary_for_payroll',
                'is_active',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'account_number' => 'encrypted',
            'iban' => 'encrypted',
            'is_primary_for_payroll' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    private function maskSensitiveValue(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return '•••• '.Str::substr($value, -4);
    }
}
