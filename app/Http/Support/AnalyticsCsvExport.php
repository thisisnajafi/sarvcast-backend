<?php

namespace App\Http\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsCsvExport
{
    /**
     * @param  array<string, scalar|null>  $metrics
     * @param  array<int, array<string, scalar|null>>  $rows
     * @param  array<int, string>  $rowHeaders
     */
    public static function stream(
        string $filename,
        array $metrics,
        array $rowHeaders,
        array $rows,
        int $dateRange,
    ): StreamedResponse {
        return response()->streamDownload(
            function () use ($metrics, $rowHeaders, $rows, $dateRange) {
                $handle = fopen('php://output', 'w');
                if ($handle === false) {
                    return;
                }

                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($handle, ['metric', 'value']);
                foreach ($metrics as $key => $value) {
                    fputcsv($handle, [$key, is_scalar($value) || $value === null ? $value : json_encode($value, JSON_UNESCAPED_UNICODE)]);
                }

                fputcsv($handle, []);
                fputcsv($handle, ['date_range_days', $dateRange]);

                if ($rowHeaders !== [] && $rows !== []) {
                    fputcsv($handle, []);
                    fputcsv($handle, $rowHeaders);
                    foreach ($rows as $row) {
                        $line = [];
                        foreach ($rowHeaders as $header) {
                            $line[] = $row[$header] ?? '';
                        }
                        fputcsv($handle, $line);
                    }
                }

                fclose($handle);
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }
}
