<?php

declare(strict_types=1);

namespace App\Support;

final class ExtraColumns
{
    public const EXACT_KEY = '__exact__';

    /**
     * Collect exact ordered headers from an imported spreadsheet, if present.
     *
     * @param  iterable<int, object>  $records
     * @return list<string>
     */
    public static function exactHeaders(iterable $records): array
    {
        $headers = [];

        foreach ($records as $record) {
            $extra = $record->extra_columns ?? null;
            if (! is_array($extra)) {
                continue;
            }

            $exact = $extra[self::EXACT_KEY] ?? null;
            if (! is_array($exact)) {
                continue;
            }

            foreach (array_keys($exact) as $header) {
                $label = trim((string) $header);
                if ($label !== '' && ! in_array($label, $headers, true)) {
                    $headers[] = $label;
                }
            }
        }

        return $headers;
    }

    /**
     * Get the exact cell value for a given imported header, formatting currency numbers when appropriate.
     */
    public static function exactValue(object $record, string $header): string
    {
        $extra = $record->extra_columns ?? null;
        if (! is_array($extra)) {
            return '—';
        }

        $exact = $extra[self::EXACT_KEY] ?? null;
        if (! is_array($exact) || ! array_key_exists($header, $exact)) {
            return '—';
        }

        $value = trim((string) ($exact[$header] ?? ''));
        if ($value === '' || $value === '-' || $value === '—') {
            return '—';
        }

        if (is_numeric($value) && self::isMoneyHeader($header)) {
            $num = (float) $value;

            return fmod($num, 1.0) === 0.0
                ? number_format($num, 0)
                : number_format($num, 2);
        }

        return $value;
    }

    /**
     * Collect unique ordered legacy dynamic column headers (excluding the exact-layout key).
     *
     * @param  iterable<int, object>  $records
     * @return list<string>
     */
    public static function headers(iterable $records): array
    {
        $headers = [];

        foreach ($records as $record) {
            $extra = $record->extra_columns ?? null;
            if (! is_array($extra)) {
                continue;
            }

            foreach (array_keys($extra) as $header) {
                $label = trim((string) $header);
                if ($label !== '' && $label !== self::EXACT_KEY && ! in_array($label, $headers, true)) {
                    $headers[] = $label;
                }
            }
        }

        return $headers;
    }

    private static function isMoneyHeader(string $header): bool
    {
        foreach (['مبلغ', 'المبلغ', 'قيمة', 'القيمة', 'تكلفة', 'التكلفة', 'الإيجار', 'الايجار', 'الغرامة', 'راتب', 'الراتب', 'amount', 'cost', 'salary'] as $needle) {
            if (mb_stripos($header, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
