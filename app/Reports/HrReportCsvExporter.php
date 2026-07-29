<?php

namespace App\Reports;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrReportCsvExporter
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $columns
     */
    public function download(
        User $actor,
        Company $scope,
        string $report,
        Collection $rows,
        array $columns,
        bool $group = false,
        string $format = 'csv',
    ): StreamedResponse {
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        activity('hr_report_exports')
            ->performedOn($scope)
            ->causedBy($actor)
            ->event('exported')
            ->withProperties([
                'company_id' => $scope->getKey(),
                'report' => $report,
                'row_count' => $rows->count(),
                'scope' => $group ? 'group' : 'company',
                'format' => $format,
            ])
            ->log('HR report exported');

        $filename = str($scope->slug.'-'.$report.'-'.now()->format('Ymd-His'))->slug()->append('.'.$format)->toString();

        if ($format === 'xlsx') {
            return $this->xlsxDownload($filename, $rows, $columns);
        }

        return response()->streamDownload(function () use ($rows, $columns): void {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, array_values($columns));
            foreach ($rows as $row) {
                fputcsv($stream, array_map(
                    fn (string $key): string => $this->stringValue($row[$key] ?? ''),
                    array_keys($columns),
                ));
            }
            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $columns
     */
    private function xlsxDownload(string $filename, Collection $rows, array $columns): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows, $columns): void {
            $temporaryPath = tempnam(sys_get_temp_dir(), 'hr-report-');
            abort_if($temporaryPath === false, 500, 'Unable to create the private export file.');

            try {
                $writer = new Writer;
                $writer->setCreator(config('app.name'));
                $writer->openToFile($temporaryPath);
                $writer->addRow(Row::fromValues(array_values($columns)));
                foreach ($rows as $row) {
                    $writer->addRow(Row::fromValues(array_map(
                        fn (string $key): string => $this->stringValue($row[$key] ?? ''),
                        array_keys($columns),
                    )));
                }
                $writer->close();
                readfile($temporaryPath);
            } finally {
                if (is_file($temporaryPath)) {
                    unlink($temporaryPath);
                }
            }
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function stringValue(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        return is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR);
    }
}
