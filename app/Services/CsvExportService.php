<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    /**
     * Download a CSV file.
     *
     * @param string $filename The filename for the download.
     * @param array $headings The headings for the CSV.
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder instance.
     * @param callable $mapCallback A callback to map each record to an array.
     * @return StreamedResponse
     */
    public function download(string $filename, array $headings, $query, callable $mapCallback): StreamedResponse
    {
        return new StreamedResponse(function () use ($headings, $query, $mapCallback) {
            $handle = fopen('php://output', 'w');
            
            // Add BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, $headings);

            $query->chunk(100, function ($records) use ($handle, $mapCallback) {
                foreach ($records as $record) {
                    fputcsv($handle, $mapCallback($record));
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
