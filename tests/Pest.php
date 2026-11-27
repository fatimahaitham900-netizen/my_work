<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/**
 * Decode a streamed XLSX (or CSV) export into plain searchable text for assertions.
 */
function extractExportText(string $binary): string
{
    if (! str_starts_with($binary, 'PK')) {
        return $binary;
    }

    $tmpPath = tempnam(sys_get_temp_dir(), 'xlsx_test_');
    if ($tmpPath === false) {
        return '';
    }

    file_put_contents($tmpPath, $binary);

    try {
        $zip = new ZipArchive;
        if ($zip->open($tmpPath) !== true) {
            return '';
        }

        $xml = (string) ($zip->getFromName('xl/worksheets/sheet1.xml') ?: '');
        $zip->close();

        return html_entity_decode(strip_tags(str_replace('</c>', ' | ', $xml)), ENT_QUOTES | ENT_XML1, 'UTF-8');
    } finally {
        @unlink($tmpPath);
    }
}
