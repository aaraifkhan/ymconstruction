<?php

namespace App\Models;

use Database\Factories\OpeningBalanceMigrationRowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'opening_balance_migration_id', 'company_id', 'source_row_number', 'account_code',
    'party_code', 'project_code', 'cost_center_code', 'description', 'debit', 'credit',
    'account_id', 'party_id', 'project_id', 'cost_center_id', 'validation_errors',
])]
class OpeningBalanceMigrationRow extends Model
{
    /** @use HasFactory<OpeningBalanceMigrationRowFactory> */
    use HasFactory;

    public function migration(): BelongsTo
    {
        return $this->belongsTo(OpeningBalanceMigration::class, 'opening_balance_migration_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:4',
            'credit' => 'decimal:4',
            'source_row_number' => 'integer',
            'validation_errors' => 'array',
        ];
    }
}
