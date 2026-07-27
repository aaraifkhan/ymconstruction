<?php

namespace App\Reports;

use Illuminate\Support\Collection;

class FinancialReportCsvExporter
{
    /** @param Collection<int, array<string, mixed>> $rows */
    public function export(Collection $rows, array $columns): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, array_values($columns));
        foreach ($rows as $row) {
            fputcsv($stream, array_map(function (string $key) use ($row): string {
                $value = $row[$key] ?? '';

                return $value instanceof \BackedEnum ? (string) $value->value : (string) $value;
            }, array_keys($columns)));
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return (string) $csv;
    }
}
