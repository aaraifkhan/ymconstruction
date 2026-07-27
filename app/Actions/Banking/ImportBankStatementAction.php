<?php

namespace App\Actions\Banking;

use App\Enums\BankReconciliationStatus;
use App\Enums\BankStatementStatus;
use App\Models\BankReconciliation;
use App\Models\BankStatement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportBankStatementAction
{
    /** @var array<int, string> */
    private const HEADERS = [
        'transaction_date',
        'value_date',
        'description',
        'reference',
        'debit',
        'credit',
        'balance',
    ];

    public function handle(
        BankStatement $statement,
        string $storagePath,
        string $originalFileName,
        User $actor,
    ): BankStatement {
        Gate::forUser($actor)->authorize('import', $statement);
        if (Str::lower(pathinfo($originalFileName, PATHINFO_EXTENSION)) !== 'csv'
            || ! Storage::disk('local')->exists($storagePath)) {
            throw ValidationException::withMessages(['statement_file' => 'Upload a private CSV bank statement file.']);
        }

        $absolutePath = Storage::disk('local')->path($storagePath);
        if (filesize($absolutePath) === false || filesize($absolutePath) > 10 * 1024 * 1024
            || ! in_array(mime_content_type($absolutePath), ['text/plain', 'text/csv', 'application/csv'], true)) {
            throw ValidationException::withMessages(['statement_file' => 'CSV statement must be a valid text file no larger than 10 MB.']);
        }

        return DB::transaction(function () use ($absolutePath, $actor, $originalFileName, $statement, $storagePath): BankStatement {
            $statement = BankStatement::query()->whereKey($statement)->lockForUpdate()->firstOrFail();
            if ($statement->status !== BankStatementStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only a draft statement may be imported.']);
            }

            $rows = $this->readRows($absolutePath);
            $runningBalance = (string) $statement->opening_balance;
            foreach ($rows as $index => $row) {
                $debit = $this->decimal($row['debit'], $index, 'debit');
                $credit = $this->decimal($row['credit'], $index, 'credit');
                if ((bccomp($debit, '0', 4) === 1) === (bccomp($credit, '0', 4) === 1)) {
                    throw ValidationException::withMessages(["rows.{$index}.debit" => 'Each row requires a positive debit or credit, never both.']);
                }
                $balance = $this->decimal($row['balance'], $index, 'balance', allowNegative: true);
                $runningBalance = bcadd(bcsub($runningBalance, $debit, 4), $credit, 4);
                if (bccomp($runningBalance, $balance, 4) !== 0) {
                    throw ValidationException::withMessages(["rows.{$index}.balance" => 'Running balance does not reconcile to opening balance and row activity.']);
                }

                $transactionDate = $this->date($row['transaction_date'], $index, 'transaction_date');
                $valueDate = blank($row['value_date']) ? null : $this->date($row['value_date'], $index, 'value_date');
                $fingerprintValues = [
                    $transactionDate,
                    $valueDate,
                    Str::squish($row['description']),
                    Str::squish($row['reference']),
                    $debit,
                    $credit,
                    $balance,
                ];
                $statement->lines()->create([
                    'company_id' => $statement->company_id,
                    'company_bank_account_id' => $statement->company_bank_account_id,
                    'line_number' => $index + 1,
                    'transaction_date' => $transactionDate,
                    'value_date' => $valueDate,
                    'description' => Str::limit(Str::squish($row['description']), 2000, ''),
                    'bank_reference' => blank($row['reference']) ? null : Str::limit(Str::squish($row['reference']), 255, ''),
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $balance,
                    'fingerprint' => hash('sha256', implode('|', $fingerprintValues)),
                ]);
            }

            if (bccomp($runningBalance, (string) $statement->closing_balance, 4) !== 0) {
                throw ValidationException::withMessages(['closing_balance' => 'Imported rows do not reconcile to the declared statement closing balance.']);
            }

            BankReconciliation::query()->create([
                'company_id' => $statement->company_id,
                'company_bank_account_id' => $statement->company_bank_account_id,
                'bank_statement_id' => $statement->getKey(),
                'period_start' => $statement->period_start,
                'period_end' => $statement->period_end,
                'status' => BankReconciliationStatus::Draft,
                'statement_closing_balance' => $statement->closing_balance,
                'prepared_by_id' => $actor->getKey(),
            ]);
            $statement->update([
                'status' => BankStatementStatus::Imported,
                'source_file_name' => Str::limit(basename($originalFileName), 255, ''),
                'source_sha256' => hash_file('sha256', $absolutePath),
                'source_storage_path' => $storagePath,
                'imported_by_id' => $actor->getKey(),
                'imported_at' => now(),
            ]);
            activity('bank_statements')->causedBy($actor)->performedOn($statement)->event('imported')
                ->withProperties([
                    'company_id' => $statement->company_id,
                    'company_bank_account_id' => $statement->company_bank_account_id,
                    'line_count' => count($rows),
                    'source_sha256' => $statement->source_sha256,
                ])->log('imported private bank statement');

            return $statement->refresh();
        }, attempts: 3);
    }

    /** @return array<int, array<string, string>> */
    private function readRows(string $absolutePath): array
    {
        $handle = fopen($absolutePath, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['statement_file' => 'The bank statement file could not be read.']);
        }

        try {
            $headers = fgetcsv($handle, escape: '');
            if ($headers === false) {
                throw ValidationException::withMessages(['statement_file' => 'The CSV statement is empty.']);
            }
            $headers[0] = Str::of($headers[0])->ltrim("\xEF\xBB\xBF")->toString();
            if ($headers !== self::HEADERS) {
                throw ValidationException::withMessages([
                    'statement_file' => 'CSV headers must exactly be: '.implode(', ', self::HEADERS).'.',
                ]);
            }

            $rows = [];
            while (($values = fgetcsv($handle, escape: '')) !== false) {
                if (count($values) === 1 && blank($values[0])) {
                    continue;
                }
                if (count($values) !== count(self::HEADERS)) {
                    throw ValidationException::withMessages(['statement_file' => 'Every CSV row must have exactly seven columns.']);
                }
                $rows[] = array_combine(self::HEADERS, array_map(fn ($value): string => trim((string) $value), $values));
                if (count($rows) > 10000) {
                    throw ValidationException::withMessages(['statement_file' => 'A statement import is limited to 10,000 rows.']);
                }
            }
        } finally {
            fclose($handle);
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['statement_file' => 'The CSV statement has no transaction rows.']);
        }

        return $rows;
    }

    private function decimal(string $value, int $index, string $field, bool $allowNegative = false): string
    {
        $pattern = $allowNegative ? '/^-?\d+(\.\d{1,4})?$/' : '/^\d+(\.\d{1,4})?$/';
        if (preg_match($pattern, $value) !== 1) {
            throw ValidationException::withMessages(["rows.{$index}.{$field}" => 'Use a plain number with no commas and at most four decimals.']);
        }

        return bcadd($value, '0', 4);
    }

    private function date(string $value, int $index, string $field): string
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw ValidationException::withMessages(["rows.{$index}.{$field}" => 'Use YYYY-MM-DD date format.']);
        }

        return $value;
    }
}
