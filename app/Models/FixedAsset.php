<?php

namespace App\Models;

use App\Enums\AssetAcquisitionSource;
use App\Enums\AssetStatus;
use App\Enums\DepreciationMethod;
use Database\Factories\FixedAssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'asset_category_id', 'vendor_bill_line_id', 'capitalization_credit_account_id',
    'custodian_employment_id', 'project_id', 'project_site_id', 'cost_center_id', 'asset_number',
    'name', 'serial_number', 'location', 'acquisition_source', 'acquired_on', 'available_for_use_on',
    'acquisition_cost', 'residual_value', 'useful_life_months', 'depreciation_method',
    'accumulated_depreciation', 'status', 'notes', 'prepared_by_id', 'submitted_by_id',
    'submitted_at', 'approved_by_id', 'approved_at', 'rejected_by_id', 'rejected_at',
    'rejection_reason', 'capitalized_by_id', 'capitalized_at', 'acquisition_journal_entry_id',
])]
class FixedAsset extends Model
{
    /** @use HasFactory<FixedAssetFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['acquisition_source' => 'manual', 'depreciation_method' => 'straight_line', 'accumulated_depreciation' => 0, 'status' => 'draft'];

    protected static function booted(): void
    {
        static::saving(function (self $asset): void {
            if (bccomp((string) $asset->acquisition_cost, '0', 4) !== 1
                || bccomp((string) $asset->residual_value, (string) $asset->acquisition_cost, 4) !== -1) {
                throw ValidationException::withMessages(['acquisition_cost' => 'Cost must be positive and residual value must be lower than cost.']);
            }
            if ($asset->available_for_use_on?->lt($asset->acquired_on)) {
                throw ValidationException::withMessages(['available_for_use_on' => 'Available-for-use date cannot precede acquisition.']);
            }
            if (! AssetCategory::query()->whereKey($asset->asset_category_id)->where('company_id', $asset->company_id)->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['asset_category_id' => 'Choose an active same-company asset category.']);
            }
            foreach (['custodian_employment_id' => Employment::class, 'project_id' => Project::class, 'project_site_id' => ProjectSite::class, 'cost_center_id' => CostCenter::class] as $field => $model) {
                if ($asset->{$field} !== null && ! $model::query()->whereKey($asset->{$field})->where('company_id', $asset->company_id)->exists()) {
                    throw ValidationException::withMessages([$field => 'Asset dimensions must belong to the asset company.']);
                }
            }
            if ($asset->project_site_id !== null && $asset->project_id !== null
                && ! ProjectSite::query()->whereKey($asset->project_site_id)->where('project_id', $asset->project_id)->exists()) {
                throw ValidationException::withMessages(['project_site_id' => 'Project site must belong to the selected project.']);
            }
            if ($asset->exists && ! in_array(self::query()->find($asset->getKey())?->status, [AssetStatus::Draft, AssetStatus::Rejected], true)) {
                $workflow = ['status', 'submitted_by_id', 'submitted_at', 'approved_by_id', 'approved_at', 'rejected_by_id', 'rejected_at', 'rejection_reason', 'capitalized_by_id', 'capitalized_at', 'acquisition_journal_entry_id', 'accumulated_depreciation', 'custodian_employment_id', 'project_id', 'project_site_id', 'cost_center_id', 'location', 'updated_at'];
                if (array_diff(array_keys($asset->getDirty()), $workflow) !== []) {
                    throw ValidationException::withMessages(['status' => 'Approved asset financial details are immutable.']);
                }
            }
        });

        static::deleting(function (self $asset): void {
            if (! in_array($asset->status, [AssetStatus::Draft, AssetStatus::Rejected], true)
                || $asset->depreciationLines()->exists()) {
                throw ValidationException::withMessages(['status' => 'Only unused draft or rejected assets may be deleted.']);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vendorBillLine(): BelongsTo
    {
        return $this->belongsTo(VendorBillLine::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function custodianEmployment(): BelongsTo
    {
        return $this->belongsTo(Employment::class, 'custodian_employment_id');
    }

    public function acquisitionJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function depreciationLines(): HasMany
    {
        return $this->hasMany(DepreciationRunLine::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(AssetTransfer::class);
    }

    public function disposal(): HasOne
    {
        return $this->hasOne(AssetDisposal::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function carryingAmount(): string
    {
        return bcsub((string) $this->acquisition_cost, (string) $this->accumulated_depreciation, 4);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('fixed_assets')->logOnlyDirty()->logFillable();
    }

    protected function casts(): array
    {
        return [
            'acquisition_source' => AssetAcquisitionSource::class, 'status' => AssetStatus::class,
            'depreciation_method' => DepreciationMethod::class, 'acquired_on' => 'date', 'available_for_use_on' => 'date',
            'acquisition_cost' => 'decimal:4', 'residual_value' => 'decimal:4', 'accumulated_depreciation' => 'decimal:4',
            'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'rejected_at' => 'datetime', 'capitalized_at' => 'datetime',
        ];
    }
}
