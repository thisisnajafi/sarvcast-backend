<?php

namespace App\Http\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminCsvExport
{
    /**
     * @param  callable(mixed): array<int, scalar|null>  $mapRow
     */
    public static function streamQuery(string $filename, array $headers, $query, callable $mapRow): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $query, $mapRow) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $headers);

            $query->chunk(500, function ($rows) use ($handle, $mapRow) {
                foreach ($rows as $row) {
                    fputcsv($handle, $mapRow($row));
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
