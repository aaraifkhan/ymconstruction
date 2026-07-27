<?php

namespace App\Models;

use Database\Factories\PartyContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id',
    'party_id',
    'name',
    'designation',
    'email',
    'phone',
    'is_primary',
    'is_active',
])]
class PartyContact extends Model
{
    /** @use HasFactory<PartyContactFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'is_primary' => false,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (PartyContact $contact): void {
            if (! Party::query()->whereKey($contact->party_id)->where('company_id', $contact->company_id)->exists()) {
                throw ValidationException::withMessages([
                    'party_id' => 'The selected party must belong to the same company.',
                ]);
            }
        });

        static::saved(function (PartyContact $contact): void {
            if ($contact->is_primary) {
                static::query()
                    ->where('party_id', $contact->party_id)
                    ->whereKeyNot($contact)
                    ->update(['is_primary' => false]);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('party_contacts')
            ->logOnly([
                'company_id',
                'party_id',
                'name',
                'designation',
                'email',
                'phone',
                'is_primary',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
