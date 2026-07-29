<?php

namespace App\Models;

use Database\Factories\HrDataMigrationRowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'hr_data_migration_id', 'company_id', 'source_row_number', 'source_key',
    'row_checksum', 'safe_row_data', 'resolved_references', 'validation_errors',
    'imported_record_type', 'imported_record_id', 'imported_record_checksum',
])]
class HrDataMigrationRow extends Model
{
    /** @use HasFactory<HrDataMigrationRowFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (self $row): void {
            $allowed = [
                'imported_record_type', 'imported_record_id', 'imported_record_checksum', 'updated_at',
            ];
            if (array_diff(array_keys($row->getDirty()), $allowed) !== []) {
                throw ValidationException::withMessages(['row' => 'Migration source evidence is immutable.']);
            }
        });

        static::deleting(fn () => throw ValidationException::withMessages([
            'row' => 'Migration source rows cannot be deleted.',
        ]));
    }

    public function migration(): BelongsTo
    {
        return $this->belongsTo(HrDataMigration::class, 'hr_data_migration_id');
    }

    protected function casts(): array
    {
        return [
            'safe_row_data' => 'array',
            'resolved_references' => 'array',
            'validation_errors' => 'array',
        ];
    }
}
