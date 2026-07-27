<?php

namespace App\Actions\Accounting;

use App\Enums\OpeningBalanceMigrationStatus;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\FinancialPeriod;
use App\Models\OpeningBalanceMigration;
use App\Models\OpeningBalanceMigrationRow;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrepareOpeningBalanceMigrationAction
{
    /** @var array<int, string> */
    private const HEADERS = ['account_code', 'party_code', 'project_code', 'cost_center_code', 'description', 'debit', 'credit'];

    public function handle(
        FinancialPeriod $period,
        User $actor,
        CarbonInterface $openingDate,
        string $filename,
        string $csv,
    ): OpeningBalanceMigration {
        if (! ($actor->hasRole('super_admin') || $actor->can('Create:OpeningBalanceMigration'))
            || ! $actor->canAccessTenant($period->company)) {
            throw ValidationException::withMessages(['authorization' => 'You are not authorized to prepare this migration.']);
        }
        if (strlen($csv) > 10 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => 'Opening-balance CSV must not exceed 10 MB.']);
        }
        if ($openingDate->lt($period->starts_on) || $openingDate->gt($period->ends_on)) {
            throw ValidationException::withMessages(['opening_date' => 'Opening date must be inside the selected period.']);
        }

        return DB::transaction(function () use ($period, $actor, $openingDate, $filename, $csv): OpeningBalanceMigration {
            $records = $this->parse($csv);
            if ($records === []) {
                throw ValidationException::withMessages(['file' => 'The migration file has no data rows.']);
            }
            $migration = OpeningBalanceMigration::create([
                'company_id' => $period->company_id,
                'financial_year_id' => $period->financial_year_id,
                'financial_period_id' => $period->getKey(),
                'opening_date' => $openingDate,
                'idempotency_key' => Str::uuid(),
                'source_filename' => basename($filename),
                'source_checksum' => hash('sha256', $csv),
                'status' => OpeningBalanceMigrationStatus::Draft,
                'prepared_by_id' => $actor->getKey(),
            ]);
            $debitTotal = '0.0000';
            $creditTotal = '0.0000';
            $validRows = 0;
            foreach ($records as $index => $record) {
                $resolved = $this->resolve($period->company_id, $record);
                $debitTotal = bcadd($debitTotal, $resolved['debit'], 4);
                $creditTotal = bcadd($creditTotal, $resolved['credit'], 4);
                if ($resolved['errors'] === []) {
                    $validRows++;
                }
                OpeningBalanceMigrationRow::create([
                    'opening_balance_migration_id' => $migration->getKey(),
                    'company_id' => $period->company_id,
                    'source_row_number' => $index + 2,
                    'account_code' => $record['account_code'],
                    'party_code' => $record['party_code'] ?: null,
                    'project_code' => $record['project_code'] ?: null,
                    'cost_center_code' => $record['cost_center_code'] ?: null,
                    'description' => $record['description'] ?: null,
                    'debit' => $resolved['debit'],
                    'credit' => $resolved['credit'],
                    'account_id' => $resolved['account_id'],
                    'party_id' => $resolved['party_id'],
                    'project_id' => $resolved['project_id'],
                    'cost_center_id' => $resolved['cost_center_id'],
                    'validation_errors' => $resolved['errors'] ?: null,
                ]);
            }
            $migration->update([
                'row_count' => count($records),
                'valid_row_count' => $validRows,
                'source_debit_total' => $debitTotal,
                'source_credit_total' => $creditTotal,
                'validation_summary' => [
                    'invalid_rows' => count($records) - $validRows,
                    'balanced' => bccomp($debitTotal, $creditTotal, 4) === 0,
                ],
            ]);
            activity('opening_balance_migrations')->causedBy($actor)->performedOn($migration)->event('dry-run')
                ->withProperties($migration->only(['row_count', 'valid_row_count', 'source_debit_total', 'source_credit_total', 'source_checksum']))
                ->log('prepared opening-balance migration dry run');

            return $migration->refresh();
        });
    }

    /** @return array<int, array<string, string>> */
    private function parse(string $csv): array
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csv);
        rewind($stream);
        $headers = fgetcsv($stream);
        if ($headers === false) {
            return [];
        }
        $headers = array_map(fn (string $header): string => Str::of($header)->trim()->lower()->toString(), $headers);
        if ($headers !== self::HEADERS) {
            throw ValidationException::withMessages(['file' => 'CSV headers must be exactly: '.implode(', ', self::HEADERS).'.']);
        }
        $records = [];
        while (($values = fgetcsv($stream)) !== false) {
            if ($values === [null] || collect($values)->every(fn ($value): bool => blank($value))) {
                continue;
            }
            if (count($values) !== count(self::HEADERS)) {
                throw ValidationException::withMessages(['file' => 'Every CSV row must contain exactly seven columns.']);
            }
            $records[] = array_combine(self::HEADERS, array_map(fn ($value): string => trim((string) $value), $values));
        }
        fclose($stream);

        return $records;
    }

    /** @param array<string, string> $record
     * @return array<string, mixed>
     */
    private function resolve(int $companyId, array $record): array
    {
        $errors = [];
        $debit = $this->money($record['debit'], 'debit', $errors);
        $credit = $this->money($record['credit'], 'credit', $errors);
        if ((bccomp($debit, '0', 4) === 1) === (bccomp($credit, '0', 4) === 1)) {
            $errors[] = 'Provide a positive debit or positive credit, never both.';
        }
        $account = Account::query()->where('company_id', $companyId)->where('code', $record['account_code'])
            ->where('is_active', true)->first();
        if ($account === null || $account->children()->exists()) {
            $errors[] = 'Account code is missing, inactive, or non-posting.';
        }
        $party = $this->dimension(Party::class, $companyId, $record['party_code'], $errors, 'party');
        $project = $this->dimension(Project::class, $companyId, $record['project_code'], $errors, 'project');
        $costCenter = $this->dimension(CostCenter::class, $companyId, $record['cost_center_code'], $errors, 'cost center');

        return [
            'debit' => $debit, 'credit' => $credit, 'account_id' => $account?->getKey(),
            'party_id' => $party?->getKey(), 'project_id' => $project?->getKey(),
            'cost_center_id' => $costCenter?->getKey(), 'errors' => $errors,
        ];
    }

    private function money(string $value, string $field, array &$errors): string
    {
        if ($value === '') {
            return '0.0000';
        }
        if (! preg_match('/^\d{1,15}(\.\d{1,4})?$/', $value)) {
            $errors[] = ucfirst($field).' must be a non-negative number with at most four decimals.';

            return '0.0000';
        }

        return number_format((float) $value, 4, '.', '');
    }

    private function dimension(string $model, int $companyId, string $code, array &$errors, string $label): mixed
    {
        if ($code === '') {
            return null;
        }
        $record = $model::query()->where('company_id', $companyId)->where('code', $code)->first();
        if ($record === null) {
            $errors[] = ucfirst($label).' code was not found in this company.';
        }

        return $record;
    }
}
