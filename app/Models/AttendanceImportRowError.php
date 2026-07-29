<?php

namespace App\Models;

use Database\Factories\AttendanceImportRowErrorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'company_id', 'attendance_import_batch_id', 'row_number', 'error_code', 'external_reference',
    'message', 'safe_row_data',
])]
class AttendanceImportRowError extends Model
{
    /** @use HasFactory<AttendanceImportRowErrorFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (AttendanceImportRowError $error): void {
            if (! AttendanceImportBatch::query()->whereKey($error->attendance_import_batch_id)->where('company_id', $error->company_id)->exists()) {
                throw ValidationException::withMessages(['attendance_import_batch_id' => 'The batch must belong to the same company.']);
            }
        });
        static::updating(fn () => throw ValidationException::withMessages(['message' => 'Import row errors are immutable.']));
        static::deleting(fn () => throw ValidationException::withMessages(['message' => 'Import row errors cannot be deleted.']));
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(AttendanceImportBatch::class, 'attendance_import_batch_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected function casts(): array
    {
        return ['safe_row_data' => 'array'];
    }
}
