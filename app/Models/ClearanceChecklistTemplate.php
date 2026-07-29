<?php

namespace App\Models;

use App\Enums\ClearanceArea;
use Database\Factories\ClearanceChecklistTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'company_id', 'code', 'name', 'area', 'description', 'is_mandatory',
    'is_active', 'sort_order',
])]
class ClearanceChecklistTemplate extends Model
{
    /** @use HasFactory<ClearanceChecklistTemplateFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = ['is_mandatory' => true, 'is_active' => true, 'sort_order' => 0];

    protected static function booted(): void
    {
        static::saving(function (self $template): void {
            if (blank($template->code) || blank($template->name)) {
                throw ValidationException::withMessages(['code' => 'Checklist code and name are required.']);
            }
            $template->code = str($template->code)->upper()->replaceMatches('/[^A-Z0-9_-]/', '-')->toString();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function clearanceItems(): HasMany
    {
        return $this->hasMany(EmployeeClearanceItem::class);
    }

    protected function casts(): array
    {
        return [
            'area' => ClearanceArea::class,
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
