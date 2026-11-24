<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BreakdownCategory;
use App\Enums\BreakdownSeverity;
use App\Enums\DriverStoppageStatus;
use App\Enums\RepairStatus;
use App\Models\EquipmentBreakdown;
use App\Support\SimpleZipReader;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EquipmentBreakdownDailyImportService
{
    /**
     * Parse uploaded file (CSV or XLSX), compare with existing database records without N+1 queries,
     * and build a structured preview with row-level diffs and category counters.
     *
     * @return array{
     *     filename: string,
     *     uploaded_at: string,
     *     total_count: int,
     *     new_count: int,
     *     changed_count: int,
     *     unchanged_count: int,
     *     rows: list<array<string, mixed>>
     * }
     */
    public function parseFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $rawRows = match ($extension) {
            'xlsx' => $this->parseXlsx($file->getRealPath()),
            'xls' => $this->parseXls($file->getRealPath()),
            default => $this->parseCsv($file->getRealPath()),
        };

        if (empty($rawRows)) {
            return [
                'filename' => $file->getClientOriginalName(),
                'uploaded_at' => now()->translatedFormat('Y-m-d H:i'),
                'total_count' => 0,
                'new_count' => 0,
                'changed_count' => 0,
                'unchanged_count' => 0,
                'rows' => [],
            ];
        }

        // Auto-detect header row among the first 10 rows
        $headerRowIndex = 0;
        $bestMatchCount = 0;
        $headerMap = [];

        $candidateRows = array_slice($rawRows, 0, 10, true);
        foreach ($candidateRows as $idx => $candidateRow) {
            $candidateMap = $this->buildHeaderMap($candidateRow);
            if (count($candidateMap) > $bestMatchCount) {
                $bestMatchCount = count($candidateMap);
                $headerRowIndex = $idx;
                $headerMap = $candidateMap;
            }
        }

        if ($bestMatchCount === 0 && ! empty($rawRows)) {
            $headerRowIndex = 0;
            $headerMap = $this->buildHeaderMap($rawRows[0]);
        }

        $dataRows = array_slice($rawRows, $headerRowIndex + 1);

        $normalizedRows = [];
        $equipmentCodes = [];

        foreach ($dataRows as $index => $rowValues) {
            $row = $this->mapRowToAttributes($headerMap, $rowValues, $index);
            if ($row === null || trim((string) ($row['equipment_code'] ?? '')) === '') {
                continue;
            }

            $normalizedRows[] = $row;
            $equipmentCodes[] = $row['equipment_code'];
        }

        // Single query to fetch all existing equipment breakdowns — ZERO N+1 queries!
        $equipmentCodes = array_values(array_unique($equipmentCodes));
        $existingRecords = EquipmentBreakdown::query()
            ->whereIn('equipment_code', $equipmentCodes)
            ->get()
            ->keyBy('equipment_code');

        // Fetch all existing active records not in the incoming equipment codes (these will be completely deleted on apply)
        $toDeleteRecords = EquipmentBreakdown::query()
            ->whereNotIn('equipment_code', $equipmentCodes)
            ->get(['id', 'equipment_code', 'equipment_name', 'brand', 'workshop_name', 'workshop_status', 'repair_status', 'breakdown_date'])
            ->map(static fn (EquipmentBreakdown $item): array => [
                'id' => $item->id,
                'equipment_code' => $item->equipment_code,
                'equipment_name' => $item->equipment_name ?: '—',
                'brand' => $item->brand ?: '—',
                'workshop_name' => $item->workshop_name ?: '—',
                'workshop_status' => $item->workshop_status ?: ($item->repair_status?->label() ?? '—'),
                'breakdown_date' => $item->breakdown_date?->format('Y-m-d') ?? '—',
            ])->all();
        $deletedCount = count($toDeleteRecords);

        $previewRows = [];
        $newCount = 0;
        $changedCount = 0;
        $unchangedCount = 0;

        foreach ($normalizedRows as $idx => $row) {
            $code = $row['equipment_code'];
            /** @var EquipmentBreakdown|null $existing */
            $existing = $existingRecords->get($code);

            if ($existing === null) {
                $status = 'new';
                $newCount++;
                $diffs = [];
            } else {
                $diffs = $this->computeDiffs($existing, $row);
                if (! empty($diffs)) {
                    $status = 'changed';
                    $changedCount++;
                } else {
                    $status = 'unchanged';
                    $unchangedCount++;
                }
            }

            $previewRows[] = [
                'index' => $idx + 1,
                'status' => $status,
                'equipment_code' => $code,
                'equipment_name' => $row['equipment_name'] ?: ($existing?->equipment_name ?? ''),
                'brand' => $row['brand'] ?: ($existing?->brand ?? ''),
                'workshop_name' => $row['workshop_name'] ?: ($existing?->workshop_name ?? ''),
                'breakdown_date' => $row['breakdown_date'] ?: ($existing?->breakdown_date?->format('Y-m-d') ?? ''),
                'severity' => $row['severity'] ?: ($existing?->severity?->value ?? 'medium'),
                'severity_label' => $this->formatSeverityLabel($row['severity'] ?: ($existing?->severity?->value ?? 'medium')),
                'fault_description' => $row['fault_description'] ?: ($existing?->fault_description ?? ''),
                'workshop_status' => $row['workshop_status'] ?: ($existing?->workshop_status ?? ''),
                'estimated_cost' => $row['estimated_cost'],
                'cost_note' => $row['cost_note'],
                'cost_display' => $this->formatCostDisplay($row['estimated_cost'], $row['cost_note']),
                'expected_repair_date' => $row['expected_repair_date'] ?: ($existing?->expected_repair_date_display !== '—' ? $existing?->expected_repair_date_display : (string) ($existing?->expected_repair_date ?? '')),
                'notes' => $row['notes'] ?: ($existing?->notes ?? ''),
                'diffs' => $diffs,
                'raw_data' => [
                    'equipment_code' => $code,
                    'equipment_name' => $row['equipment_name'] !== '' ? $row['equipment_name'] : null,
                    'brand' => $row['brand'] !== '' ? $row['brand'] : null,
                    'workshop_name' => $row['workshop_name'] !== '' ? $row['workshop_name'] : null,
                    'breakdown_date' => $row['breakdown_date'] ?: ($existing?->breakdown_date?->format('Y-m-d') ?? now()->format('Y-m-d')),
                    'severity' => $row['severity'] ?: BreakdownSeverity::Medium->value,
                    'fault_description' => $row['fault_description'] !== '' ? $row['fault_description'] : null,
                    'workshop_status' => $row['workshop_status'] !== '' ? $row['workshop_status'] : null,
                    'repair_status' => $row['repair_status'],
                    'spare_parts_delay' => $row['spare_parts_delay'],
                    'estimated_cost' => $row['estimated_cost'],
                    'cost_note' => $row['cost_note'],
                    'expected_repair_date' => $row['expected_repair_date'] ?: null,
                    'notes' => $row['notes'] !== '' ? $row['notes'] : null,
                    'category' => $existing?->category?->value ?? BreakdownCategory::Mechanical->value,
                ],
            ];
        }

        return [
            'filename' => $file->getClientOriginalName(),
            'uploaded_at' => now()->format('Y-m-d H:i'),
            'total_count' => count($previewRows),
            'new_count' => $newCount,
            'changed_count' => $changedCount,
            'unchanged_count' => $unchangedCount,
            'deleted_count' => $deletedCount,
            'to_delete_rows' => $toDeleteRecords,
            'rows' => $previewRows,
        ];
    }

    /**
     * Directly import and apply file contents to database in one step,
     * updating new records and purging obsolete ones.
     *
     * @return array{created: int, updated: int, deleted: int, total: int}
     */
    public function importDirect(UploadedFile $file): array
    {
        $preview = $this->parseFile($file);

        if ($preview['total_count'] === 0) {
            return ['created' => 0, 'updated' => 0, 'deleted' => 0, 'total' => 0];
        }

        return $this->applyImport($preview);
    }

    /**
     * Persist validated preview rows into the database atomically and purge old records.
     *
     * @param  array<string, mixed>  $preview
     * @return array{created: int, updated: int, deleted: int, total: int}
     */
    public function applyImport(array $preview): array
    {
        $rows = $preview['rows'] ?? [];
        if (empty($rows)) {
            return ['created' => 0, 'updated' => 0, 'deleted' => 0, 'total' => 0];
        }

        return DB::transaction(function () use ($rows): array {
            $createdCount = 0;
            $updatedCount = 0;
            $incomingCodes = [];

            foreach ($rows as $row) {
                $code = (string) $row['equipment_code'];
                $incomingCodes[] = $code;
                $status = $row['status'] ?? 'unchanged';
                $attributes = (array) $row['raw_data'];

                /** @var EquipmentBreakdown|null $breakdown */
                $breakdown = EquipmentBreakdown::withTrashed()->where('equipment_code', $code)->first();

                if ($breakdown !== null) {
                    if ($breakdown->trashed()) {
                        $breakdown->restore();
                        $createdCount++;
                    } elseif ($status !== 'unchanged') {
                        $updatedCount++;
                    }
                    $breakdown->update($attributes);
                } else {
                    $breakdown = EquipmentBreakdown::query()->create($attributes);
                    $createdCount++;
                }

                // If marked as repaired and has waiting linked driver stoppages, cascade resolution
                if ($breakdown->repair_status === RepairStatus::Repaired) {
                    $breakdown->driverStoppages()
                        ->where('status', DriverStoppageStatus::Waiting->value)
                        ->update(['status' => DriverStoppageStatus::Resolved->value]);
                }
            }

            // Completely delete all old equipment breakdowns that are not present in this imported file!
            $deletedCount = EquipmentBreakdown::query()
                ->whereNotIn('equipment_code', $incomingCodes)
                ->delete();

            DashboardService::clearCache();

            return [
                'created' => $createdCount,
                'updated' => $updatedCount,
                'deleted' => $deletedCount,
                'total' => count($rows),
            ];
        });
    }

    /**
     * Delete all active equipment breakdown records in a single transaction and clear dashboard cache.
     */
    public function purgeAll(): int
    {
        return DB::transaction(static function (): int {
            $deletedCount = EquipmentBreakdown::query()->delete();
            DashboardService::clearCache();

            return $deletedCount;
        });
    }

    /**
     * Downloadable sample CSV template with standard headers and realistic reference records.
     */
    public function generateTemplateStream(): StreamedResponse
    {
        $headers = [
            'م',
            'المعدة',
            'النوع',
            'الكود',
            'الورشة',
            'تاريخ التوقف',
            'حجم العطل',
            'العطل',
            'الموقف',
            'المبلغ',
            'تاريخ الإصلاح',
            'ملاحظات الموقف',
        ];

        $sampleRows = [
            [
                1,
                'شنيور',
                'Sandvik',
                'RD_000001',
                'ورشه نجران',
                now()->subDays(15)->format('Y-m-d'),
                'ثقيل',
                'تربو + بخاخات + طلمبة الهيدروليك',
                'جاري الكشف',
                '',
                now()->addDays(5)->format('Y-m-d'),
                'تم تركيب القطع وتوريد لواقص مفصل نظام الهيدروليك',
            ],
            [
                2,
                'مولد',
                'CAT',
                'GN_000058',
                'ورشه نجران',
                now()->subDays(10)->format('Y-m-d'),
                'خفيف',
                'عطل كهرباء (كمبيوتر + بوكس + حساسات)',
                'دعم مالي',
                '10695',
                '',
                'عرض سعر واحد من دبي وتم توجيه الورشة لإحضار عينات',
            ],
            [
                3,
                'شيول',
                'KOMATSU',
                'WL_000003',
                'ورشه نجران',
                now()->subDays(4)->format('Y-m-d'),
                'متوسط',
                'جير + محرك + طلمبة هيدروليك',
                'توريد قطع غيار',
                '25000',
                now()->addDays(7)->format('Y-m-d'),
                'تم عمل عمرة جير وبانتظار وصول الكنترول من الرياض',
            ],
        ];

        return new StreamedResponse(static function () use ($headers, $sampleRows): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Microsoft Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($sampleRows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="equipment-breakdowns-daily-template.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Compute differences between database record and incoming row values.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, array{label: string, old: string, new: string}>
     */
    protected function computeDiffs(EquipmentBreakdown $existing, array $row): array
    {
        $diffs = [];

        $comparisons = [
            'equipment_name' => [
                'label' => 'المعدة',
                'old' => (string) ($existing->equipment_name ?? ''),
                'new' => (string) ($row['equipment_name'] ?? ''),
            ],
            'brand' => [
                'label' => 'النوع',
                'old' => (string) ($existing->brand ?? ''),
                'new' => (string) ($row['brand'] ?? ''),
            ],
            'workshop_name' => [
                'label' => 'الورشة',
                'old' => (string) ($existing->workshop_name ?? ''),
                'new' => (string) ($row['workshop_name'] ?? ''),
            ],
            'breakdown_date' => [
                'label' => 'تاريخ التوقف',
                'old' => $existing->breakdown_date?->format('Y-m-d') ?? '',
                'new' => (string) ($row['breakdown_date'] ?? ''),
            ],
            'severity' => [
                'label' => 'حجم العطل',
                'old' => $this->formatSeverityLabel($existing->severity?->value ?? ''),
                'new' => $this->formatSeverityLabel($row['severity'] ?? ''),
            ],
            'fault_description' => [
                'label' => 'العطل',
                'old' => (string) ($existing->fault_description ?? ''),
                'new' => (string) ($row['fault_description'] ?? ''),
            ],
            'workshop_status' => [
                'label' => 'الموقف',
                'old' => (string) ($existing->workshop_status ?? ''),
                'new' => (string) ($row['workshop_status'] ?? ''),
            ],
            'cost' => [
                'label' => 'المبلغ',
                'old' => $this->formatCostDisplay((string) $existing->estimated_cost, $existing->cost_note),
                'new' => $this->formatCostDisplay($row['estimated_cost'], $row['cost_note']),
            ],
            'expected_repair_date' => [
                'label' => 'تاريخ الإصلاح',
                'old' => $existing->expected_repair_date_display !== '—' ? $existing->expected_repair_date_display : (string) ($existing->expected_repair_date ?? ''),
                'new' => (string) ($row['expected_repair_date'] ?? ''),
            ],
            'notes' => [
                'label' => 'ملاحظات الموقف',
                'old' => (string) ($existing->notes ?? ''),
                'new' => (string) ($row['notes'] ?? ''),
            ],
        ];

        foreach ($comparisons as $key => $comp) {
            $oldNorm = trim($comp['old']);
            $newNorm = trim($comp['new']);

            // Ignore changes if incoming row is empty but existing record has data (preserve existing unless explicitly set)
            if ($newNorm !== '' && $oldNorm !== $newNorm) {
                $diffs[$key] = [
                    'label' => $comp['label'],
                    'old' => $oldNorm !== '' ? $oldNorm : '—',
                    'new' => $newNorm,
                ];
            }
        }

        return $diffs;
    }

    /**
     * Map CSV or Excel header row to recognized model attribute keys.
     *
     * @param  list<mixed>  $headers
     * @return array<int, string>
     */
    protected function buildHeaderMap(array $headers): array
    {
        $map = [];

        $dictionary = [
            'equipment_code' => ['الكود', 'كود', 'كود المعدة', 'كود_المعدة', 'رقم المعدة', 'رقم_المعدة', 'كودالمعدة', 'رقم الأصل', 'رقم الاصل', 'الأصل', 'الاصل', 'لوحة', 'اللوحة', 'رقم اللوحة', 'الشاسيه', 'الهيكل', 'equipment_code', 'code', 'id', 'tag', 'asset', 'equipment_id', 'no'],
            'equipment_name' => ['المعدة', 'المعده', 'اسم المعدة', 'اسم المعده', 'نوع المعدة', 'نوع المعده', 'البيان', 'equipment_name', 'name', 'equipment', 'vehicle'],
            'brand' => ['النوع', 'الماركة', 'الماركه', 'الشركة المصنعة', 'الشركه المصنعه', 'الصانع', 'brand', 'make', 'model'],
            'workshop_name' => ['الورشة', 'الورشه', 'اسم الورشة', 'اسم الورشه', 'مقر الورشة', 'مقر الورشه', 'الموقع', 'مقر الصيانة', 'مكان العطل', 'workshop_name', 'workshop', 'location'],
            'breakdown_date' => ['تاريخ التوقف', 'تاريخ العطل', 'تاريخ الدخول', 'تاريخ التوقف عن العمل', 'تاريخ البلاغ', 'تاريخ الايقاف', 'breakdown_date', 'entry_date', 'date', 'stop_date'],
            'severity' => ['حجم العطل', 'حجم_العطل', 'الخطورة', 'الشدة', 'درجة العطل', 'severity', 'priority'],
            'fault_description' => ['العطل', 'وصف العطل', 'نوع العطل', 'تفاصيل العطل', 'بيان العطل', 'المشكلة', 'fault_description', 'fault', 'description', 'issue'],
            'workshop_status' => ['الموقف', 'موقف الورشة', 'موقف الورشه', 'الحالة', 'حالة المعدة', 'موقف المعدة', 'حالة الصيانة', 'الموقف الحالي', 'workshop_status', 'status'],
            'cost' => ['المبلغ', 'التكلفة', 'التكلفة التقديرية', 'تكلفة الاصلاح', 'المبلغ التقديري', 'القيمة', 'estimated_cost', 'cost', 'amount', 'price'],
            'expected_repair_date' => ['تاريخ الإصلاح', 'تاريخ الاصلاح', 'تاريخ الإصلاح المتوقع', 'تاريخ الخروج', 'تاريخ الانتهاء', 'تاريخ التسليم', 'expected_repair_date', 'repair_date'],
            'notes' => ['ملاحظات الموقف', 'ملاحظات', 'الملاحظات', 'ملاحظة', 'تفاصيل إضافية', 'notes', 'remarks', 'comments'],
        ];

        foreach ($headers as $colIndex => $rawHeader) {
            $normalized = $this->cleanHeaderString((string) $rawHeader);
            if ($normalized === '' || $normalized === 'م' || $normalized === '#' || $normalized === 'ايام التوقف' || $normalized === 'أيام التوقف') {
                continue;
            }

            // Phase 1: Exact matches first
            $matched = false;
            foreach ($dictionary as $attribute => $synonyms) {
                foreach ($synonyms as $synonym) {
                    if ($normalized === $this->cleanHeaderString($synonym)) {
                        $map[$colIndex] = $attribute;
                        $matched = true;
                        break 2;
                    }
                }
            }

            if ($matched) {
                continue;
            }

            // Phase 2: Prefix / substring matches as fallback
            foreach ($dictionary as $attribute => $synonyms) {
                // Don't assign the same attribute multiple times via substring fallback if already assigned
                if (in_array($attribute, $map, true)) {
                    continue;
                }

                foreach ($synonyms as $synonym) {
                    $cleanSynonym = $this->cleanHeaderString($synonym);
                    if ($cleanSynonym === '' || mb_strlen($cleanSynonym) < 3) {
                        continue;
                    }
                    if ($attribute === 'equipment_code' && (str_contains($normalized, 'اصلاح') || str_contains($normalized, 'توقف'))) {
                        continue;
                    }
                    if (str_contains($normalized, $cleanSynonym)) {
                        $map[$colIndex] = $attribute;
                        break 2;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * Map raw row columns into strongly typed and normalized attribute payload.
     *
     * @param  array<int, string>  $headerMap
     * @param  list<mixed>  $row
     * @return array<string, mixed>|null
     */
    protected function mapRowToAttributes(array $headerMap, array $row, int $rowIndex = 0): ?array
    {
        $rawAttributes = [];

        foreach ($headerMap as $colIndex => $attrKey) {
            $rawAttributes[$attrKey] = isset($row[$colIndex]) ? trim((string) $row[$colIndex]) : '';
        }

        // Check if row has any non-empty cells
        $hasData = false;
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                $hasData = true;
                break;
            }
        }
        if (! $hasData) {
            return null;
        }

        $code = trim((string) ($rawAttributes['equipment_code'] ?? ''));
        if ($code === '') {
            $col0 = isset($row[0]) ? trim((string) $row[0]) : '';
            $col1 = isset($row[1]) ? trim((string) $row[1]) : '';
            if (preg_match('/^[A-Za-z0-9_-]{2,30}$/', $col0) && ! is_numeric($col0)) {
                $code = $col0;
            } elseif (preg_match('/^[A-Za-z0-9_-]{2,30}$/', $col1) && ! is_numeric($col1)) {
                $code = $col1;
            } elseif ($col0 !== '' && is_numeric($col0)) {
                $code = 'EQ-'.$col0;
            } else {
                $code = sprintf('EQ-%04d', $rowIndex + 1);
            }
        }

        // Severity normalization
        $rawSeverity = (string) ($rawAttributes['severity'] ?? '');
        $severity = match (true) {
            str_contains($rawSeverity, 'ثقيل') || str_contains($rawSeverity, 'حرج') || strtolower($rawSeverity) === 'critical' => BreakdownSeverity::Critical->value,
            str_contains($rawSeverity, 'خفيف') || str_contains($rawSeverity, 'بسيط') || strtolower($rawSeverity) === 'minor' => BreakdownSeverity::Minor->value,
            default => BreakdownSeverity::Medium->value,
        };

        // Cost normalization: check if numeric or text cost note
        $rawCost = (string) ($rawAttributes['cost'] ?? '');
        $cleanNumericCost = preg_replace('/[^\d.]/', '', $rawCost) ?? '';
        $isNumeric = is_numeric($cleanNumericCost) && strlen($cleanNumericCost) > 0 && ! preg_match('/[\p{Arabic}]/u', $rawCost);

        if ($isNumeric) {
            $estimatedCost = number_format(round((float) $cleanNumericCost, 2), 2, '.', '');
            $costNote = null;
        } elseif ($rawCost !== '' && $rawCost !== '—' && $rawCost !== '-') {
            $estimatedCost = '0.00';
            $costNote = $rawCost;
        } else {
            $estimatedCost = '0.00';
            $costNote = null;
        }

        // Workshop status & repair status normalization
        $workshopStatus = (string) ($rawAttributes['workshop_status'] ?? '');
        $isRepaired = str_contains($workshopStatus, 'تم الإصلاح') || str_contains($workshopStatus, 'تم الاصلاح') || strtolower($workshopStatus) === 'repaired';
        $repairStatus = $isRepaired ? RepairStatus::Repaired->value : RepairStatus::InWorkshop->value;
        $sparePartsDelay = str_contains($workshopStatus, 'قطع') || str_contains($workshopStatus, 'توريد');

        return [
            'equipment_code' => $code,
            'equipment_name' => (string) ($rawAttributes['equipment_name'] ?? ''),
            'brand' => (string) ($rawAttributes['brand'] ?? ''),
            'workshop_name' => (string) ($rawAttributes['workshop_name'] ?? ''),
            'breakdown_date' => $this->parseDateString((string) ($rawAttributes['breakdown_date'] ?? '')),
            'severity' => $severity,
            'fault_description' => (string) ($rawAttributes['fault_description'] ?? ''),
            'workshop_status' => $workshopStatus,
            'repair_status' => $repairStatus,
            'spare_parts_delay' => $sparePartsDelay,
            'estimated_cost' => $estimatedCost,
            'cost_note' => $costNote,
            'expected_repair_date' => $this->parseDateOrTextString((string) ($rawAttributes['expected_repair_date'] ?? '')),
            'notes' => (string) ($rawAttributes['notes'] ?? ''),
        ];
    }

    /**
     * Parse date or preserve informational text note (e.g. "من الاعتماد البنكي" or "المرحلة الاولى").
     */
    protected function parseDateOrTextString(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '' || $trimmed === '—' || $trimmed === '-') {
            return null;
        }

        // Numeric Excel date serial (e.g. 45678)
        if (is_numeric($trimmed) && (int) $trimmed > 20000 && (int) $trimmed < 60000) {
            try {
                $base = Carbon::create(1899, 12, 30);

                return $base?->addDays((int) $trimmed)->format('Y-m-d');
            } catch (\Throwable) {
                // fallback
            }
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'm/d/Y', 'Y.m.d', 'd.m.Y'];
        foreach ($formats as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $trimmed);
                if ($d instanceof DateTimeInterface) {
                    return $d->format('Y-m-d');
                }
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            if (preg_match('/\d{4}[-\/]\d{1,2}[-\/]\d{1,2}/', $trimmed) || preg_match('/\d{1,2}[-\/]\d{1,2}[-\/]\d{4}/', $trimmed)) {
                return Carbon::parse($trimmed)->format('Y-m-d');
            }
        } catch (\Throwable) {
            // ignore
        }

        // Retain text phrase as-is
        return $trimmed;
    }

    /**
     * Normalize equipment code for cross-sheet lookup and matching (e.g. "GN_000058" -> "GN-58").
     */
    protected function normalizeCodeKey(string $code): string
    {
        $c = strtoupper(trim($code));
        if (preg_match('/^([A-Z]+)[-_0]*([0-9]+)$/', $c, $m)) {
            return $m[1].'-'.(int) $m[2];
        }

        return $c;
    }

    /**
     * Parse date string into Y-m-d format.
     */
    protected function parseDateString(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '' || $trimmed === '—' || $trimmed === '-') {
            return null;
        }

        // Check if numeric Excel timestamp (e.g. 45678)
        if (is_numeric($trimmed) && (int) $trimmed > 20000 && (int) $trimmed < 60000) {
            try {
                $base = Carbon::create(1899, 12, 30);

                return $base?->addDays((int) $trimmed)->format('Y-m-d');
            } catch (\Throwable) {
                // fallback
            }
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'm/d/Y', 'Y.m.d', 'd.m.Y'];
        foreach ($formats as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $trimmed);
                if ($d instanceof DateTimeInterface) {
                    return $d->format('Y-m-d');
                }
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($trimmed)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Format severity key into Arabic display text.
     */
    protected function formatSeverityLabel(string $severity): string
    {
        return match ($severity) {
            BreakdownSeverity::Critical->value, 'critical', 'حرج', 'ثقيل' => 'ثقيل',
            BreakdownSeverity::Minor->value, 'minor', 'خفيف', 'بسيط' => 'خفيف',
            default => 'متوسط',
        };
    }

    /**
     * Format cost value or cost note into display string.
     */
    protected function formatCostDisplay(string|float|null $estimatedCost, ?string $costNote): string
    {
        if ($costNote !== null && trim($costNote) !== '') {
            return trim($costNote);
        }

        $cost = (float) $estimatedCost;

        return $cost > 0 ? number_format($cost, 0).' ر.س' : '—';
    }

    protected function cleanHeaderString(string $header): string
    {
        $str = preg_replace('/[\r\n\t_]/u', ' ', $header) ?? '';
        // Remove Arabic tashkeel / harakat
        $str = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $str) ?? '';
        // Normalize Arabic letters
        $str = preg_replace('/[إأآا]/u', 'ا', $str) ?? '';
        $str = preg_replace('/ة/u', 'ه', $str) ?? '';
        $str = preg_replace('/[ىي]/u', 'ي', $str) ?? '';
        $str = preg_replace('/[^\p{L}\p{N} ]/u', '', $str) ?? '';

        return strtolower(trim(preg_replace('/\s+/', ' ', $str) ?? ''));
    }

    /**
     * Parse CSV files with delimiter and encoding auto-detection.
     *
     * @return list<list<string>>
     */
    protected function parseCsv(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return [];
        }

        // Detect and convert encoding if needed
        if (! mb_check_encoding($content, 'UTF-8')) {
            $converted = @mb_convert_encoding($content, 'UTF-8', ['Windows-1256', 'ISO-8859-6', 'ASCII']);
            if ($converted !== false) {
                $content = $converted;
            }
        }

        // Strip UTF-8 BOM if present
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        // Normalize line endings
        $content = str_replace(["\r\n", "\r"], "\n", trim($content));
        if ($content === '') {
            return [];
        }

        // Auto-detect delimiter from first non-empty line
        $firstNewlinePos = strpos($content, "\n");
        $firstLine = $firstNewlinePos === false ? $content : substr($content, 0, $firstNewlinePos);
        $delimiter = ',';
        $maxCount = 0;
        foreach ([',', ';', "\t", '|'] as $delim) {
            $count = substr_count($firstLine, $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $delimiter = $delim;
            }
        }

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return [];
        }

        fwrite($handle, $content);
        rewind($handle);

        $rows = [];
        while (($parsed = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            if ($parsed === [null] || empty($parsed)) {
                continue;
            }
            $cleaned = array_map(static fn (mixed $v): string => trim((string) $v), $parsed);
            if (implode('', $cleaned) === '') {
                continue;
            }
            $rows[] = $cleaned;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Parse XLS files, which might be XLSX disguised as XLS, or HTML table disguised as XLS, or CSV.
     *
     * @return list<list<string>>
     */
    protected function parseXls(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return [];
        }

        // If it starts with PK zip header, it's actually an OpenXML XLSX
        if (str_starts_with($content, "PK\x03\x04")) {
            return $this->parseXlsx($path);
        }

        // If it contains an HTML table (very common in Arab enterprise exports named .xls)
        if (str_contains($content, '<table') || str_contains($content, '<html') || str_contains($content, '<tr')) {
            return $this->parseHtmlTable($content);
        }

        // Fallback to CSV parsing
        return $this->parseCsv($path);
    }

    /**
     * Parse HTML table contents from exported spreadsheet files.
     *
     * @return list<list<string>>
     */
    protected function parseHtmlTable(string $html): array
    {
        $rows = [];
        if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $html, $trMatches)) {
            foreach ($trMatches[1] as $trContent) {
                if (preg_match_all('/<(?:td|th)[^>]*>(.*?)<\/(?:td|th)>/si', $trContent, $tdMatches)) {
                    $row = [];
                    foreach ($tdMatches[1] as $cellHtml) {
                        $text = strip_tags($cellHtml);
                        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $row[] = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
                    }
                    if (! empty($row) && implode('', $row) !== '') {
                        $rows[] = $row;
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * Native XLSX parser using ZipArchive and SimpleXML without external third-party dependencies.
     * Accurately scores and identifies breakdown-specific worksheets, ignoring unrelated asset or driver sheets.
     *
     * @return list<list<string>>
     */
    protected function parseXlsx(string $path): array
    {
        $zip = new SimpleZipReader;
        if (! $zip->open($path)) {
            return [];
        }

        // 1. Read shared strings if present
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = @simplexml_load_string($sharedStringsXml);
            if ($xml instanceof SimpleXMLElement && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Discover sheets from workbook.xml and rels
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $wbXml = $zip->getFromName('xl/workbook.xml');
        $sheetTargets = [];
        if ($relsXml !== false) {
            $rels = @simplexml_load_string($relsXml);
            if ($rels) {
                foreach ($rels->Relationship as $rel) {
                    $sheetTargets[(string) $rel['Id']] = (string) $rel['Target'];
                }
            }
        }

        $sheets = [];
        $activeTabIndex = null;
        if ($wbXml !== false) {
            $wb = @simplexml_load_string($wbXml);
            if ($wb && isset($wb->bookViews->workbookView['activeTab'])) {
                $activeTabIndex = (int) $wb->bookViews->workbookView['activeTab'];
            }
            if ($wb && isset($wb->sheets->sheet)) {
                $sheetIndex = 0;
                foreach ($wb->sheets->sheet as $s) {
                    $name = (string) $s['name'];
                    $state = strtolower((string) ($s['state'] ?? 'visible'));
                    $rId = (string) $s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                    $target = ltrim($sheetTargets[$rId] ?? '', '/');
                    if ($target !== '') {
                        $sheets[] = [
                            'index' => $sheetIndex,
                            'name' => $name,
                            'hidden' => in_array($state, ['hidden', 'veryhidden'], true),
                            'isActive' => $activeTabIndex !== null && $sheetIndex === $activeTabIndex,
                            'target' => str_starts_with($target, 'xl/') ? $target : 'xl/'.$target,
                        ];
                    }
                    $sheetIndex++;
                }
            }
        }

        // Fallback if workbook structure wasn't read
        if (empty($sheets)) {
            foreach ($zip->entryNames() as $i => $name) {
                if (str_starts_with($name, 'xl/worksheets/sheet') && str_ends_with($name, '.xml')) {
                    $sheets[] = [
                        'index' => $i,
                        'name' => basename($name, '.xml'),
                        'hidden' => false,
                        'isActive' => false,
                        'target' => $name,
                    ];
                }
            }
        }

        // 3. Score and parse each visible sheet
        $breakdownSheets = [];
        $generalSheets = [];

        foreach ($sheets as $sheetInfo) {
            // Skip hidden sheets so stale/print/archived sheets never pollute imported data
            if (! empty($sheetInfo['hidden'])) {
                continue;
            }

            $content = $zip->getFromName($sheetInfo['target']);
            if ($content === false) {
                continue;
            }

            $rawRows = $this->extractRowsFromSheetXml($content, $sharedStrings);
            if (empty($rawRows)) {
                continue;
            }

            $cleanName = $this->cleanHeaderString($sheetInfo['name']);

            // Detect best header map
            $bestHeaderMap = [];
            $headerIndex = -1;
            for ($i = 0; $i < min(6, count($rawRows)); $i++) {
                $map = $this->buildHeaderMap($rawRows[$i]);
                if (count($map) > count($bestHeaderMap)) {
                    $bestHeaderMap = $map;
                    $headerIndex = $i;
                }
            }

            $uniqueAttrs = array_unique($bestHeaderMap);
            $hasWorkshop = in_array('workshop_name', $uniqueAttrs, true);
            $hasFault = in_array('fault_description', $uniqueAttrs, true);
            $hasCode = in_array('equipment_code', $uniqueAttrs, true);

            $isDriverOrAssetSheet = str_contains($cleanName, 'مناسيب') || str_contains($cleanName, 'fixed') || str_contains($cleanName, 'تحليل') || str_contains($cleanName, 'رئيسي') || str_contains($cleanName, 'استبعاد');

            if (! $isDriverOrAssetSheet && $hasCode && ($hasWorkshop || $hasFault) && count($bestHeaderMap) >= 6) {
                $priority = match (true) {
                    str_contains($cleanName, 'قابل للاصلاح') => 100,
                    ! empty($sheetInfo['isActive']) => 95,
                    str_contains($cleanName, 'مؤجل') => 80,
                    default => 50,
                };

                $breakdownSheets[] = [
                    'name' => $sheetInfo['name'],
                    'priority' => $priority,
                    'headerMap' => $bestHeaderMap,
                    'headerIndex' => $headerIndex,
                    'rows' => array_slice($rawRows, $headerIndex + 1),
                ];
            } else {
                $generalSheets[] = [
                    'name' => $sheetInfo['name'],
                    'rows' => $rawRows,
                    'matchCount' => count($bestHeaderMap),
                ];
            }
        }

        // Return exact rows from the primary visible breakdown sheet (1-to-1 fidelity, no hidden sheet invention)
        if (! empty($breakdownSheets)) {
            usort($breakdownSheets, static fn ($a, $b) => $b['priority'] <=> $a['priority']);

            $primarySheet = $breakdownSheets[0];
            $headerMap = $primarySheet['headerMap'];
            $standardHeaders = ['م', 'المعدة', 'النوع', 'الكود', 'الورشة', 'تاريخ التوقف', 'حجم العطل', 'العطل', 'الموقف', 'المبلغ', 'تاريخ الإصلاح', 'ملاحظات الموقف'];
            $outputRows = [$standardHeaders];

            $counter = 1;
            foreach ($primarySheet['rows'] as $rIdx => $rawRow) {
                $mapped = $this->mapRowToAttributes($headerMap, $rawRow, $rIdx);
                if ($mapped === null || trim((string) ($mapped['equipment_code'] ?? '')) === '') {
                    continue;
                }

                // Must have at least one other non-empty field besides code
                $hasOtherData = trim((string) ($mapped['equipment_name'] ?? '')) !== ''
                    || trim((string) ($mapped['workshop_name'] ?? '')) !== ''
                    || trim((string) ($mapped['fault_description'] ?? '')) !== ''
                    || trim((string) ($mapped['brand'] ?? '')) !== '';
                if (! $hasOtherData) {
                    continue;
                }

                $outputRows[] = [
                    (string) $counter++,
                    (string) ($mapped['equipment_name'] ?? ''),
                    (string) ($mapped['brand'] ?? ''),
                    (string) ($mapped['equipment_code'] ?? ''),
                    (string) ($mapped['workshop_name'] ?? ''),
                    (string) ($mapped['breakdown_date'] ?? ''),
                    (string) ($mapped['severity'] ?? 'medium'),
                    (string) ($mapped['fault_description'] ?? ''),
                    (string) ($mapped['workshop_status'] ?? ''),
                    (string) (($mapped['cost_note'] ?? '') !== '' ? $mapped['cost_note'] : ($mapped['estimated_cost'] ?? '0.00')),
                    (string) ($mapped['expected_repair_date'] ?? ''),
                    (string) ($mapped['notes'] ?? ''),
                ];
            }

            return $outputRows;
        }

        // Fallback: Return best matching general sheet
        if (! empty($generalSheets)) {
            usort($generalSheets, static fn ($a, $b) => $b['matchCount'] <=> $a['matchCount']);

            return $generalSheets[0]['rows'];
        }

        return [];
    }

    protected function extractRowsFromSheetXml(string $sheetXml, array $sharedStrings): array
    {
        $xml = @simplexml_load_string($sheetXml);
        if (! ($xml instanceof SimpleXMLElement) || ! isset($xml->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            $lastCol = -1;

            foreach ($row->c as $cell) {
                $cellRef = (string) ($cell['r'] ?? '');
                $colIndex = $cellRef !== '' ? $this->columnRefToIndex($cellRef) : ($lastCol + 1);

                while ($lastCol + 1 < $colIndex) {
                    $rowData[] = '';
                    $lastCol++;
                }

                $type = (string) ($cell['t'] ?? '');
                $val = isset($cell->v) ? (string) $cell->v : '';

                if ($type === 's' && isset($sharedStrings[(int) $val])) {
                    $cellValue = $sharedStrings[(int) $val];
                } elseif ($type === 'inlineStr') {
                    if (isset($cell->is->t)) {
                        $cellValue = (string) $cell->is->t;
                    } elseif (isset($cell->is->r)) {
                        $text = '';
                        foreach ($cell->is->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $cellValue = $text;
                    } else {
                        $cellValue = (string) ($cell->is ?? '');
                    }
                } else {
                    $cellValue = $val;
                }

                $rowData[] = trim($cellValue);
                $lastCol = $colIndex;
            }

            if (! empty($rowData) && implode('', $rowData) !== '') {
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    protected function columnRefToIndex(string $cellRef): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($cellRef)) ?? '';
        if ($letters === '') {
            return 0;
        }

        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }

        return max(0, $index - 1);
    }
}
