<?php

namespace App\Models;

use Database\Factories\JournalLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'journal_entry_id', 'company_id', 'related_company_id', 'line_number', 'account_id', 'account_code_snapshot',
    'account_name_snapshot', 'description', 'debit', 'credit', 'party_id', 'project_id',
    'project_site_id', 'cost_center_id', 'employment_id', 'company_bank_account_id',
    'fixed_asset_id',
])]
class JournalLine extends Model
{
    /** @use HasFactory<JournalLineFactory> */
    use HasFactory;

    protected $attributes = ['debit' => 0, 'credit' => 0];

    protected static function booted(): void
    {
        static::saving(function (self $line): void {
            $entry = JournalEntry::query()->find($line->journal_entry_id);
            if ($entry === null || (int) $entry->company_id !== (int) $line->company_id || ! $entry->isEditable()) {
                throw ValidationException::withMessages(['journal_entry_id' => 'Journal lines may only be changed on an editable journal in the same company.']);
            }

            $debitPositive = bccomp((string) $line->debit, '0', 4) === 1;
            $creditPositive = bccomp((string) $line->credit, '0', 4) === 1;
            if ($debitPositive === $creditPositive) {
                throw ValidationException::withMessages(['debit' => 'Each line requires a positive debit or positive credit, never both.']);
            }

            $account = Account::query()->whereKey($line->account_id)->where('company_id', $line->company_id)->first();
            if ($account === null) {
                throw ValidationException::withMessages(['account_id' => 'The account must belong to the journal company.']);
            }

            $line->account_code_snapshot = $account->code;
            $line->account_name_snapshot = $account->name;
            $line->validateDimensions();
        });

        static::deleting(function (self $line): void {
            if (! $line->journalEntry()->firstOrFail()->isEditable()) {
                throw ValidationException::withMessages(['journal_entry_id' => 'Posted or in-review journal lines are immutable.']);
            }
        });
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function relatedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'related_company_id');
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

    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function companyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class);
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function bankReconciliationMatches(): HasMany
    {
        return $this->hasMany(BankReconciliationMatch::class);
    }

    protected function casts(): array
    {
        return ['debit' => 'decimal:4', 'credit' => 'decimal:4', 'line_number' => 'integer'];
    }

    private function validateDimensions(): void
    {
        $dimensions = [
            'party_id' => Party::class,
            'project_id' => Project::class,
            'project_site_id' => ProjectSite::class,
            'cost_center_id' => CostCenter::class,
            'employment_id' => Employment::class,
            'company_bank_account_id' => CompanyBankAccount::class,
            'fixed_asset_id' => FixedAsset::class,
        ];

        if ($this->related_company_id !== null && (
            (int) $this->related_company_id === (int) $this->company_id
            || ! Company::query()->whereKey($this->related_company_id)->where('is_active', true)->exists()
        )) {
            throw ValidationException::withMessages(['related_company_id' => 'Related company must be a different active company.']);
        }

        foreach ($dimensions as $field => $model) {
            $id = $this->{$field};
            if ($id !== null && ! $model::query()->whereKey($id)->where('company_id', $this->company_id)->exists()) {
                throw ValidationException::withMessages([$field => 'Every journal dimension must belong to the journal company.']);
            }
        }

        if ($this->project_site_id !== null && $this->project_id !== null
            && ! ProjectSite::query()->whereKey($this->project_site_id)->where('project_id', $this->project_id)->exists()) {
            throw ValidationException::withMessages(['project_site_id' => 'The project site must belong to the selected project.']);
        }
    }
}
