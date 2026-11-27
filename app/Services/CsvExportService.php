<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Support\StyledXlsxWriter;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    /**
     * Stream a cleanly formatted RTL Excel (.xlsx) download (or CSV when ?format=csv is explicitly requested)
     * and record an audit log entry.
     *
     * @param  class-string  $auditableClass
     * @param  list<string>  $headers
     * @param  iterable<int, list<string|int|float|null>>  $rows
     * @param  array<string, mixed>  $filters
     */
    public function stream(
        string $filename,
        string $auditableClass,
        array $headers,
        iterable $rows,
        array $filters = [],
        ?string $sheetTitle = null,
    ): StreamedResponse {
        $useCsv = app()->bound('request') && strtolower((string) request()->query('format', '')) === 'csv';

        $bufferedRows = [];
        foreach ($rows as $row) {
            $bufferedRows[] = array_map($this->sanitizeCell(...), $row);
        }

        $finalFilename = $useCsv
            ? preg_replace('/\.xlsx$/i', '.csv', $filename) ?? $filename
            : preg_replace('/\.csv$/i', '.xlsx', $filename) ?? $filename;

        if (! $useCsv && ! str_ends_with(strtolower($finalFilename), '.xlsx')) {
            $finalFilename .= '.xlsx';
        }

        if (AuditLog::$recordingEnabled) {
            AuditLog::query()->create([
                'user_id' => Auth::id(),
                'auditable_type' => $auditableClass,
                'auditable_id' => 0,
                'event' => AuditEvent::Exported,
                'old_values' => null,
                'new_values' => [
                    'filename' => $finalFilename,
                    'format' => $useCsv ? 'csv' : 'xlsx',
                    'row_count' => count($bufferedRows),
                    'filters' => array_filter(
                        $filters,
                        static fn (mixed $value): bool => $value !== null && $value !== '',
                    ),
                ],
                'ip_address' => app()->bound('request') ? request()->ip() : null,
            ]);
        }

        if ($useCsv) {
            return $this->streamCsv($finalFilename, $headers, $bufferedRows);
        }

        $tmpPath = StyledXlsxWriter::writeToTempFile(
            $headers,
            $bufferedRows,
            $sheetTitle ?? 'تقرير المكتب التنفيذي',
            $sheetTitle ?? 'التقرير',
        );

        return response()->streamDownload(
            static function () use ($tmpPath): void {
                try {
                    readfile($tmpPath);
                } finally {
                    @unlink($tmpPath);
                }
            },
            $finalFilename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store, no-cache',
            ],
        );
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $bufferedRows
     */
    protected function streamCsv(string $filename, array $headers, array $bufferedRows): StreamedResponse
    {
        return response()->streamDownload(
            static function () use ($headers, $bufferedRows): void {
                $output = fopen('php://output', 'wb');
                if ($output === false) {
                    return;
                }

                // UTF-8 BOM ensures Arabic text renders cleanly in Microsoft Excel
                fwrite($output, "\xEF\xBB\xBF");
                fputcsv($output, $headers);

                foreach ($bufferedRows as $row) {
                    fputcsv($output, $row);
                }

                fclose($output);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache',
            ],
        );
    }

    protected function sanitizeCell(string|int|float|null $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        // Prevent spreadsheet formula injection while keeping valid negative numbers intact
        if (
            in_array($trimmed[0], ['=', '+', '@', "\t", "\r"], true)
            || ($trimmed[0] === '-' && ! is_numeric($trimmed))
        ) {
            return "'".$value;
        }

        return $value;
    }
}
