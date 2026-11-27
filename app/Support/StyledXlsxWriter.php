<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Lightweight, zero-dependency Microsoft Excel (.xlsx) OpenXML writer with
 * RTL layout, dark executive header styling, zebra striping, borders,
 * auto-sized Arabic columns, frozen header rows, and AutoFilter.
 */
final class StyledXlsxWriter
{
    /**
     * Build a styled .xlsx workbook in a temporary file and return its path.
     * Caller is responsible for unlinking the file after streaming.
     *
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    public static function writeToTempFile(
        array $headers,
        array $rows,
        string $title = 'تقرير المكتب التنفيذي',
        string $sheetName = 'التقرير',
    ): string {
        $tmpPath = tempnam(sys_get_temp_dir(), 'exec_xlsx_');
        if ($tmpPath === false) {
            throw new RuntimeException('Unable to create temporary file for Excel export.');
        }

        $zip = new ZipArchive;
        if ($zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpPath);
            throw new RuntimeException('Unable to initialize Excel archive.');
        }

        $safeSheetName = self::safeSheetTitle($sheetName);
        $sheetXml = self::buildWorksheetXml($headers, $rows, $title);

        $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
        $zip->addFromString('_rels/.rels', self::rootRelsXml());
        $zip->addFromString('xl/workbook.xml', self::workbookXml($safeSheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
        $zip->addFromString('xl/styles.xml', self::stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        return $tmpPath;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    private static function buildWorksheetXml(array $headers, array $rows, string $title): string
    {
        $colCount = max(1, count($headers));
        $lastCol = self::columnLetter($colCount);
        $colMaxWidths = [];

        foreach ($headers as $idx => $header) {
            $colMaxWidths[$idx] = mb_strlen(trim($header), 'UTF-8');
        }

        foreach ($rows as $row) {
            foreach ($row as $idx => $val) {
                if ($idx >= $colCount) {
                    continue;
                }
                $len = mb_strlen($val, 'UTF-8');
                if ($len > ($colMaxWidths[$idx] ?? 0)) {
                    $colMaxWidths[$idx] = $len;
                }
            }
        }

        // Column widths with generous Arabic padding
        $colsXml = '<cols>';
        for ($i = 0; $i < $colCount; $i++) {
            $colNum = $i + 1;
            $charLen = $colMaxWidths[$i] ?? 10;
            $width = min(46, max(12, (int) ceil($charLen * 1.35) + 5));
            $colsXml .= "<col min=\"{$colNum}\" max=\"{$colNum}\" width=\"{$width}\" customWidth=\"1\"/>";
        }
        $colsXml .= '</cols>';

        // Row 1: Executive Title Banner (style 1)
        $banner = self::xmlEscape($title.' — تاريخ التصدير: '.now()->format('Y-m-d'));
        $rowsXml = '<row r="1" ht="32" customHeight="1">'
            ."<c r=\"A1\" t=\"inlineStr\" s=\"1\"><is><t>{$banner}</t></is></c>";
        for ($i = 1; $i < $colCount; $i++) {
            $col = self::columnLetter($i + 1);
            $rowsXml .= "<c r=\"{$col}1\" s=\"1\"/>";
        }
        $rowsXml .= '</row>';

        // Row 2: Column Headers (style 2)
        $rowsXml .= '<row r="2" ht="28" customHeight="1">';
        foreach ($headers as $idx => $header) {
            $col = self::columnLetter($idx + 1);
            $escaped = self::xmlEscape(trim($header));
            $rowsXml .= "<c r=\"{$col}2\" t=\"inlineStr\" s=\"2\"><is><t>{$escaped}</t></is></c>";
        }
        $rowsXml .= '</row>';

        // Rows 3+: Data rows with zebra striping (styles 3/4 text, 5/6 integer, 7/8 decimal)
        $rowNum = 3;
        foreach ($rows as $rowIndex => $row) {
            $isEven = ($rowIndex % 2) === 1;
            $rowsXml .= "<row r=\"{$rowNum}\" ht=\"22\" customHeight=\"1\">";

            for ($colIdx = 0; $colIdx < $colCount; $colIdx++) {
                $col = self::columnLetter($colIdx + 1);
                $cellVal = $row[$colIdx] ?? '';

                if (self::shouldWriteAsNumeric($cellVal)) {
                    $num = (float) $cellVal;
                    $isInt = floor($num) == $num;
                    $styleId = $isInt
                        ? ($isEven ? 6 : 5)
                        : ($isEven ? 8 : 7);
                    $rowsXml .= "<c r=\"{$col}{$rowNum}\" s=\"{$styleId}\"><v>{$num}</v></c>";
                } else {
                    $styleId = $isEven ? 4 : 3;
                    $escaped = self::xmlEscape($cellVal);
                    $rowsXml .= "<c r=\"{$col}{$rowNum}\" t=\"inlineStr\" s=\"{$styleId}\"><is><t>{$escaped}</t></is></c>";
                }
            }

            $rowsXml .= '</row>';
            $rowNum++;
        }

        $lastDataRow = max(2, $rowNum - 1);
        $mergeXml = $colCount > 1
            ? "<mergeCells count=\"1\"><mergeCell ref=\"A1:{$lastCol}1\"/></mergeCells>"
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            .'<sheetViews><sheetView tabSelected="1" rightToLeft="1" workbookViewId="0">'
            .'<pane ySplit="2" topLeftCell="A3" activePane="bottomLeft" state="frozen"/>'
            .'</sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="22"/>'
            .$colsXml
            .'<sheetData>'.$rowsXml.'</sheetData>'
            ."<autoFilter ref=\"A2:{$lastCol}{$lastDataRow}\"/>"
            .$mergeXml
            .'<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
            .'<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            .'</worksheet>';
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="3">'
            .'<font><sz val="11"/><color rgb="FF0F172A"/><name val="Arial"/></font>'
            .'<font><b/><sz val="13"/><color rgb="FFFFFFFF"/><name val="Arial"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Arial"/></font>'
            .'</fonts>'
            .'<fills count="5">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF020617"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="3">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border>'
            .'<left style="thin"><color rgb="FF334155"/></left>'
            .'<right style="thin"><color rgb="FF334155"/></right>'
            .'<top style="thin"><color rgb="FF334155"/></top>'
            .'<bottom style="thin"><color rgb="FF334155"/></bottom>'
            .'<diagonal/>'
            .'</border>'
            .'<border>'
            .'<left style="thin"><color rgb="FFCBD5E1"/></left>'
            .'<right style="thin"><color rgb="FFCBD5E1"/></right>'
            .'<top style="thin"><color rgb="FFCBD5E1"/></top>'
            .'<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            .'<diagonal/>'
            .'</border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="9">'
            // 0: default
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            // 1: title banner
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            // 2: header row
            .'<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            // 3: odd data row text
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="2" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            // 4: even data row text (zebra)
            .'<xf numFmtId="0" fontId="0" fillId="4" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            // 5: odd data row integer (#,##0 -> numFmtId 3)
            .'<xf numFmtId="3" fontId="0" fillId="0" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            // 6: even data row integer
            .'<xf numFmtId="3" fontId="0" fillId="4" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            // 7: odd data row decimal (#,##0.00 -> numFmtId 4)
            .'<xf numFmtId="4" fontId="0" fillId="0" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            // 8: even data row decimal
            .'<xf numFmtId="4" fontId="0" fillId="4" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private static function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbookXml(string $sheetName): string
    {
        $escaped = self::xmlEscape($sheetName);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$escaped.'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private static function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }

    private static function shouldWriteAsNumeric(string $value): bool
    {
        if ($value === '' || ! is_numeric($value)) {
            return false;
        }

        if (str_starts_with($value, '0') && ! str_starts_with($value, '0.') && strlen($value) > 1) {
            return false;
        }

        if (strlen(preg_replace('/\D/', '', $value) ?? '') >= 10) {
            return false;
        }

        return true;
    }

    private static function safeSheetTitle(string $title): string
    {
        $clean = preg_replace('/[\\\\\/\?\*\[\]:]+/u', ' ', trim($title)) ?? 'التقرير';

        return mb_substr($clean !== '' ? $clean : 'التقرير', 0, 31, 'UTF-8');
    }

    private static function xmlEscape(string $value): string
    {
        // Strip invalid XML 1.0 control characters
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

