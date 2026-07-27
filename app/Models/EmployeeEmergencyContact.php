<?php

namespace App\Models;

use Database\Factories\EmployeeEmergencyContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['employee_id', 'name', 'relationship', 'mobile', 'address', 'is_primary'])]
class EmployeeEmergencyContact extends Model
{
    /** @use HasFactory<EmployeeEmergencyContactFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['is_primary' => false];

    protected static function booted(): void
    {
        static::saved(function (EmployeeEmergencyContact $contact): void {
            if (! $contact->is_primary) {
                return;
            }

            self::query()
                ->whereBelongsTo($contact->employee)
                ->whereKeyNot($contact)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employee_emergency_contacts')
            ->logOnly(['employee_id', 'name', 'relationship', 'is_primary'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }
}
