<?php

namespace App\Models;

use App\Enums\AssetCustodyEventType;
use Database\Factories\EmployeeAssetCustodyEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'company_id', 'employee_asset_custody_id', 'fixed_asset_id', 'employment_id',
    'event_type', 'effective_on', 'snapshot', 'reason', 'actor_id',
])]
#[Hidden(['snapshot', 'reason'])]
class EmployeeAssetCustodyEvent extends Model
{
    /** @use HasFactory<EmployeeAssetCustodyEventFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Asset custody events are immutable.'));
        static::deleting(fn () => throw new LogicException('Asset custody events are immutable.'));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function custody(): BelongsTo
    {
        return $this->belongsTo(EmployeeAssetCustody::class, 'employee_asset_custody_id');
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return [
            'event_type' => AssetCustodyEventType::class,
            'effective_on' => 'date',
            'snapshot' => 'encrypted:array',
            'reason' => 'encrypted',
        ];
    }
}
