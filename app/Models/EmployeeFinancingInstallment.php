<?php

namespace App\Models;

use App\Enums\EmployeeFinancingInstallmentStatus;
use Database\Factories\EmployeeFinancingInstallmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'company_id', 'employee_financing_id', 'schedule_version', 'installment_number',
    'due_date', 'principal_due', 'finance_charge_due', 'total_due',
    'principal_recovered', 'finance_charge_recovered', 'principal_waived',
    'finance_charge_waived', 'status',
])]
class EmployeeFinancingInstallment extends Model
{
    /** @use HasFactory<EmployeeFinancingInstallmentFactory> */
    use HasFactory;

    protected $attributes = [
        'schedule_version' => 1,
        'principal_recovered' => 0,
        'finance_charge_recovered' => 0,
        'principal_waived' => 0,
        'finance_charge_waived' => 0,
        'status' => 'pending',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $installment): void {
            if (! EmployeeFinancing::query()->whereKey($installment->employee_financing_id)
                ->where('company_id', $installment->company_id)->exists()) {
                throw ValidationException::withMessages(['employee_financing_id' => 'The financing must belong to the same company.']);
            }
            if (bccomp((string) $installment->total_due, bcadd((string) $installment->principal_due, (string) $installment->finance_charge_due, 4), 4) !== 0) {
                throw ValidationException::withMessages(['total_due' => 'Installment total must equal principal plus finance charge.']);
            }
        });
        static::updating(fn () => throw ValidationException::withMessages([
            'status' => 'Installments change only through controlled recovery, waiver, or reschedule actions.',
        ]));
        static::deleting(fn () => throw ValidationException::withMessages([
            'status' => 'Installment history cannot be deleted.',
        ]));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employeeFinancing(): BelongsTo
    {
        return $this->belongsTo(EmployeeFinancing::class);
    }

    public function outstandingAmount(): string
    {
        return bcsub(
            (string) $this->total_due,
            bcadd(
                bcadd((string) $this->principal_recovered, (string) $this->finance_charge_recovered, 4),
                bcadd((string) $this->principal_waived, (string) $this->finance_charge_waived, 4),
                4,
            ),
            4,
        );
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'status' => EmployeeFinancingInstallmentStatus::class,
            'principal_due' => 'decimal:4',
            'finance_charge_due' => 'decimal:4',
            'total_due' => 'decimal:4',
            'principal_recovered' => 'decimal:4',
            'finance_charge_recovered' => 'decimal:4',
            'principal_waived' => 'decimal:4',
            'finance_charge_waived' => 'decimal:4',
        ];
    }
}
