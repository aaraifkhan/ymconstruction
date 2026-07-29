<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['company_id', 'prefix', 'padding', 'next_number'])]
class EmployeeCodeSequence extends Model
{
    protected $attributes = [
        'prefix' => 'EMP',
        'padding' => 5,
        'next_number' => 1,
    ];

    protected static function booted(): void
    {
        static::saving(function (EmployeeCodeSequence $sequence): void {
            if ($sequence->padding < 3 || $sequence->padding > 12) {
                throw ValidationException::withMessages([
                    'padding' => 'Employee-code padding must be between 3 and 12 digits.',
                ]);
            }

            if ($sequence->exists
                && $sequence->isDirty('next_number')
                && $sequence->next_number < $sequence->getOriginal('next_number')) {
                throw ValidationException::withMessages([
                    'next_number' => 'The employee-code sequence cannot be moved backwards.',
                ]);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected function casts(): array
    {
        return [
            'padding' => 'integer',
            'next_number' => 'integer',
        ];
    }
}
