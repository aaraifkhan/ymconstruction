<?php

namespace App\Models;

use App\Enums\PartyRole;
use Database\Factories\PartyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id',
    'code',
    'name',
    'legal_name',
    'roles',
    'tax_number',
    'email',
    'phone',
    'address',
    'payment_terms_days',
    'is_active',
])]
class Party extends Model
{
    /** @use HasFactory<PartyFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'payment_terms_days' => 0,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (Party $party): void {
            $roles = collect($party->roles)
                ->filter(fn (mixed $role): bool => is_string($role))
                ->unique()
                ->values();

            if ($roles->isEmpty() || $roles->contains(fn (string $role): bool => PartyRole::tryFrom($role) === null)) {
                throw ValidationException::withMessages([
                    'roles' => 'Select at least one valid party role.',
                ]);
            }

            $party->roles = $roles->all();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PartyContact::class);
    }

    public function clientProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'client_party_id');
    }

    public function consultantProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'consultant_party_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function hasRole(PartyRole $role): bool
    {
        return in_array($role->value, $this->roles, true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('parties')
            ->logOnly([
                'company_id',
                'code',
                'name',
                'legal_name',
                'roles',
                'tax_number',
                'email',
                'phone',
                'address',
                'payment_terms_days',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'roles' => 'array',
            'payment_terms_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
