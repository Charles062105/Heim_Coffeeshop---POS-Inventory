<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Stream a CSV formatted for Microsoft Excel with UTF-8 BOM.
     *
     * @param  array  $headers  Column header names
     * @param  iterable  $rows  Generator, array, or collection
     */
    public static function streamCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $responseHeaders = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 Byte Order Mark (BOM) so Microsoft Excel opens characters & currency properly
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, (array) $row);
            }

            fclose($handle);
        }, 200, $responseHeaders);
    }
}
