<?php

namespace App\Models;

use App\Enums\HrDataMigrationStatus;
use App\Enums\HrDataMigrationType;
use Database\Factories\HrDataMigrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'type', 'idempotency_key', 'source_filename', 'source_path',
    'source_checksum', 'status', 'row_count', 'valid_row_count', 'imported_row_count',
    'source_totals', 'imported_totals', 'validation_summary', 'prepared_by_id',
    'validated_by_id', 'validated_at', 'imported_by_id', 'imported_at',
    'rolled_back_by_id', 'rolled_back_at', 'rollback_reason',
])]
class HrDataMigration extends Model
{
    /** @use HasFactory<HrDataMigrationFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'status' => HrDataMigrationStatus::Draft->value,
        'row_count' => 0,
        'valid_row_count' => 0,
        'imported_row_count' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $migration): void {
            if (! $migration->exists) {
                return;
            }

            $persisted = self::query()->whereKey($migration)->firstOrFail();
            $allowed = match ($persisted->status) {
                HrDataMigrationStatus::Draft => [
                    'source_path', 'status', 'valid_row_count', 'validation_summary',
                    'validated_by_id', 'validated_at', 'updated_at',
                ],
                HrDataMigrationStatus::Validated => [
                    'status', 'imported_row_count', 'imported_totals', 'imported_by_id',
                    'imported_at', 'updated_at',
                ],
                HrDataMigrationStatus::Imported => [
                    'status', 'rolled_back_by_id', 'rolled_back_at', 'rollback_reason', 'updated_at',
                ],
                HrDataMigrationStatus::Failed, HrDataMigrationStatus::RolledBack => [],
            };

            if (array_diff(array_keys($migration->getDirty()), $allowed) !== []) {
                throw ValidationException::withMessages([
                    'status' => 'Migration evidence is immutable outside its controlled workflow.',
                ]);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(HrDataMigrationRow::class)->orderBy('source_row_number');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('hr_data_migrations')->logOnly([
            'company_id', 'type', 'source_filename', 'source_checksum', 'status',
            'row_count', 'valid_row_count', 'imported_row_count', 'prepared_by_id',
            'validated_by_id', 'imported_by_id', 'rolled_back_by_id',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'type' => HrDataMigrationType::class,
            'status' => HrDataMigrationStatus::class,
            'source_totals' => 'array',
            'imported_totals' => 'array',
            'validation_summary' => 'array',
            'validated_at' => 'datetime',
            'imported_at' => 'datetime',
            'rolled_back_at' => 'datetime',
        ];
    }
}
