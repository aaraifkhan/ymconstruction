<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\HrDocumentApplicability;
use App\Enums\MaritalStatus;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'user_id',
    'full_name',
    'father_or_husband_name',
    'cnic',
    'date_of_birth',
    'gender',
    'marital_status',
    'nationality',
    'blood_group',
    'address',
    'mobile',
    'alternate_contact',
    'email',
    'is_active',
])]
#[Hidden(['cnic', 'cnic_hash'])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = [
        'nationality' => 'Pakistani',
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (Employee $employee): void {
            if (! $employee->isDirty('cnic')) {
                return;
            }

            $normalizedCnic = static::normalizeCnic($employee->cnic);
            $employee->cnic = $normalizedCnic;
            $employee->cnic_hash = $normalizedCnic === null
                ? null
                : hash_hmac('sha256', $normalizedCnic, config('app.key'));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employments(): HasMany
    {
        return $this->hasMany(Employment::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmployeeEmergencyContact::class);
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(EmployeeQualification::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(EmployeeExperience::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(EmployeeBankAccount::class);
    }

    public function maskedCnic(): ?string
    {
        if (blank($this->cnic)) {
            return null;
        }

        return '•••••-•••••••-'.Str::substr($this->cnic, -1);
    }

    public function isEmployedBy(Company $company): bool
    {
        return $this->employments()
            ->whereBelongsTo($company)
            ->exists();
    }

    /**
     * @return Collection<int, HrDocumentType>
     */
    public function missingRequiredHrDocumentTypes(Company $company): Collection
    {
        return $company->hrDocumentTypes()
            ->where('applicability', HrDocumentApplicability::Employee)
            ->where('is_active', true)
            ->where('is_required', true)
            ->whereDoesntHave(
                'documents',
                fn ($query) => $query
                    ->where('documentable_type', self::class)
                    ->where('documentable_id', $this->getKey()),
            )
            ->orderBy('name')
            ->get();
    }

    public static function hashCnic(?string $cnic): ?string
    {
        $normalizedCnic = static::normalizeCnic($cnic);

        return $normalizedCnic === null
            ? null
            : hash_hmac('sha256', $normalizedCnic, config('app.key'));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employees')
            ->logOnly([
                'user_id',
                'full_name',
                'gender',
                'marital_status',
                'nationality',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'cnic' => 'encrypted',
            'date_of_birth' => 'date',
            'gender' => Gender::class,
            'marital_status' => MaritalStatus::class,
            'is_active' => 'boolean',
        ];
    }

    private static function normalizeCnic(?string $cnic): ?string
    {
        if (blank($cnic)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $cnic);

        return blank($digits) ? null : $digits;
    }
}
