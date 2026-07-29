<?php

namespace App\Models;

use Database\Factories\WarningLetterTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['company_id', 'code', 'name', 'level', 'subject', 'body', 'requires_response', 'is_active'])]
#[Hidden(['body'])]
class WarningLetterTemplate extends Model
{
    /** @use HasFactory<WarningLetterTemplateFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['requires_response' => false, 'is_active' => true];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(EmployeeWarning::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('warning_letter_templates')->logOnly([
            'company_id', 'code', 'name', 'level', 'subject', 'requires_response', 'is_active',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['body' => 'encrypted', 'requires_response' => 'boolean', 'is_active' => 'boolean'];
    }
}
