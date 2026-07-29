<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'company_id',
    'employment_id',
    'event_type',
    'effective_on',
    'changed_fields',
    'before_snapshot',
    'after_snapshot',
    'recorded_by_id',
])]
class EmploymentChange extends Model
{
    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Employment history is immutable.'));
        static::deleting(fn (): never => throw new LogicException('Employment history is immutable.'));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    protected function casts(): array
    {
        return [
            'effective_on' => 'date',
            'changed_fields' => 'array',
            'before_snapshot' => 'array',
            'after_snapshot' => 'array',
        ];
    }
}
