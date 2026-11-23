<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BreakdownCategory;
use App\Enums\BreakdownSeverity;
use App\Enums\DriverStoppageStatus;
use App\Enums\RepairStatus;
use App\Models\BreakdownDriverStoppage;
use App\Models\EquipmentBreakdown;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EquipmentBreakdownService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<EquipmentBreakdown>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = EquipmentBreakdown::query()
            ->withCount([
                'driverStoppages as waiting_stoppages_count' => static fn (Builder $sub): Builder => $sub->where('status', DriverStoppageStatus::Waiting->value),
            ]);

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('equipment_code', 'like', "%{$search}%")
                    ->orWhere('equipment_name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('workshop_name', 'like', "%{$search}%")
                    ->orWhere('fault_description', 'like', "%{$search}%")
                    ->orWhere('workshop_status', 'like', "%{$search}%")
                    ->orWhere('cost_note', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $workshopName = trim((string) ($filters['workshop_name'] ?? ''));
        if ($workshopName !== '') {
            $query->where('workshop_name', $workshopName);
        }

        $workshopStatus = trim((string) ($filters['workshop_status'] ?? ''));
        if ($workshopStatus !== '') {
            $query->where('workshop_status', $workshopStatus);
        }

        $equipmentName = trim((string) ($filters['equipment_name'] ?? ''));
        if ($equipmentName !== '') {
            $query->where('equipment_name', $equipmentName);
        }

        $brand = trim((string) ($filters['brand'] ?? ''));
        if ($brand !== '') {
            $query->where('brand', $brand);
        }

        $category = (string) ($filters['category'] ?? '');
        if ($category !== '' && BreakdownCategory::tryFrom($category) !== null) {
            $query->where('category', $category);
        }

        $severity = (string) ($filters['severity'] ?? '');
        if ($severity !== '' && BreakdownSeverity::tryFrom($severity) !== null) {
            $query->where('severity', $severity);
        }

        $repairStatus = (string) ($filters['repair_status'] ?? '');
        if ($repairStatus !== '' && RepairStatus::tryFrom($repairStatus) !== null) {
            $query->where('repair_status', $repairStatus);
        }

        $sparePartsDelay = (string) ($filters['spare_parts_delay'] ?? '');
        if ($sparePartsDelay === '1') {
            $query->where('spare_parts_delay', true);
        } elseif ($sparePartsDelay === '0') {
            $query->where('spare_parts_delay', false);
        }

        $focus = (string) ($filters['focus'] ?? '');
        if ($focus === 'critical') {
            $query->critical();
        } elseif ($focus === 'active') {
            $query->active();
        }

        $dateFrom = (string) ($filters['date_from'] ?? '');
        if ($dateFrom !== '') {
            $query->whereDate('breakdown_date', '>=', $dateFrom);
        }

        $dateTo = (string) ($filters['date_to'] ?? '');
        if ($dateTo !== '') {
            $query->whereDate('breakdown_date', '<=', $dateTo);
        }

        return $query->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, EquipmentBreakdown>
     */
    public function paginate(array $filters, int $perPage = 120): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @return list<string>
     */
    public function workshops(): array
    {
        return EquipmentBreakdown::query()
            ->whereNotNull('workshop_name')
            ->where('workshop_name', '!=', '')
            ->distinct()
            ->orderBy('workshop_name')
            ->pluck('workshop_name')
            ->all();
    }

    /**
     * @return list<string>
     */
    public function workshopStatuses(): array
    {
        return EquipmentBreakdown::query()
            ->whereNotNull('workshop_status')
            ->where('workshop_status', '!=', '')
            ->distinct()
            ->orderBy('workshop_status')
            ->pluck('workshop_status')
            ->all();
    }

    /**
     * @return list<string>
     */
    public function equipmentNames(): array
    {
        return EquipmentBreakdown::query()
            ->whereNotNull('equipment_name')
            ->where('equipment_name', '!=', '')
            ->distinct()
            ->orderBy('equipment_name')
            ->pluck('equipment_name')
            ->all();
    }

    /**
     * @return list<string>
     */
    public function brands(): array
    {
        return EquipmentBreakdown::query()
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->all();
    }

    /**
     * Build a pivot matrix (Row Dimension x Workshops) with cell counts, row totals, and workshop totals.
     *
     * @param  'brand'|'equipment_name'|'workshop_status'  $rowColumn
     * @return array{
     *     workshops: list<string>,
     *     rows: list<array{label: string, raw_value: string, counts: array<string, int>, total: int, percentage: float}>,
     *     column_totals: array<string, int>,
     *     grand_total: int
     * }
     */
    public function buildWorkshopMatrix(string $rowColumn): array
    {
        $workshops = $this->workshops();
        $records = EquipmentBreakdown::query()
            ->selectRaw("{$rowColumn} as row_key, workshop_name, COUNT(*) as aggregate_count")
            ->groupBy($rowColumn, 'workshop_name')
            ->get();

        $grouped = [];
        $columnTotals = array_fill_keys($workshops, 0);
        $grandTotal = 0;

        foreach ($records as $item) {
            $rawValue = trim((string) ($item->row_key ?? ''));
            $label = $rawValue !== '' ? $rawValue : 'غير محدد';
            $workshop = trim((string) ($item->workshop_name ?? ''));
            $count = (int) $item->aggregate_count;

            if ($workshop !== '' && ! in_array($workshop, $workshops, true)) {
                $workshops[] = $workshop;
                $columnTotals[$workshop] = 0;
            }

            if (! isset($grouped[$label])) {
                $grouped[$label] = [
                    'label' => $label,
                    'raw_value' => $rawValue,
                    'counts' => [],
                    'total' => 0,
                ];
            }

            if ($workshop !== '') {
                $grouped[$label]['counts'][$workshop] = ($grouped[$label]['counts'][$workshop] ?? 0) + $count;
                $columnTotals[$workshop] = ($columnTotals[$workshop] ?? 0) + $count;
            }

            $grouped[$label]['total'] += $count;
            $grandTotal += $count;
        }

        $rows = array_values($grouped);
        usort($rows, static fn (array $a, array $b): int => $b['total'] <=> $a['total'] ?: strcmp($a['label'], $b['label']));

        foreach ($rows as &$row) {
            $row['percentage'] = $grandTotal > 0 ? round(($row['total'] / $grandTotal) * 100, 1) : 0.0;
        }
        unset($row);

        return [
            'workshops' => $workshops,
            'rows' => $rows,
            'column_totals' => $columnTotals,
            'grand_total' => $grandTotal,
        ];
    }

    /**
     * @return array{total: int, critical: int, spare_parts_delay: int, repaired: int}
     */
    public function kpis(): array
    {
        return [
            'total' => EquipmentBreakdown::query()->count(),
            'critical' => EquipmentBreakdown::query()->critical()->count(),
            'spare_parts_delay' => EquipmentBreakdown::query()->waitingParts()->count(),
            'repaired' => EquipmentBreakdown::query()->where('repair_status', RepairStatus::Repaired->value)->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): EquipmentBreakdown
    {
        return EquipmentBreakdown::query()->create($data);
    }

    /**
     * Update breakdown and optionally cascade-update any linked waiting idle drivers upon repair.
     *
     * @param  array<string, mixed>  $data
     * @return array{breakdown: EquipmentBreakdown, resolved_stoppages_count: int}
     */
    public function update(
        EquipmentBreakdown $breakdown,
        array $data,
        bool $resolveLinkedStoppages = false,
        DriverStoppageStatus $linkedStatus = DriverStoppageStatus::Resolved,
    ): array {
        return DB::transaction(function () use ($breakdown, $data, $resolveLinkedStoppages, $linkedStatus): array {
            $breakdown->update($data);
            $breakdown->refresh();

            $resolvedCount = 0;

            if ($breakdown->repair_status === RepairStatus::Repaired && $resolveLinkedStoppages) {
                $waitingStoppages = $breakdown->driverStoppages()
                    ->where('status', DriverStoppageStatus::Waiting->value)
                    ->get();

                foreach ($waitingStoppages as $stoppage) {
                    $stoppage->update(['status' => $linkedStatus]);
                    $resolvedCount++;
                }
            }

            return [
                'breakdown' => $breakdown,
                'resolved_stoppages_count' => $resolvedCount,
            ];
        });
    }

    public function delete(EquipmentBreakdown $breakdown): void
    {
        $breakdown->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $records = $this->filteredQuery($filters)->get();

        $headers = [
            'م',
            'المعدة',
            'النوع',
            'الكود',
            'الورشة',
            'تاريخ التوقف',
            'أيام التوقف',
            'حجم العطل',
            'العطل',
            'الموقف',
            'المبلغ',
            'تاريخ الإصلاح',
            'ملاحظات الموقف',
        ];

        $rows = $records->values()->map(static function (EquipmentBreakdown $item, int $index): array {
            $sizeLabel = match ($item->severity) {
                BreakdownSeverity::Critical => 'ثقيل',
                BreakdownSeverity::Medium => 'متوسط',
                BreakdownSeverity::Minor => 'خفيف',
                default => '—',
            };

            $amountDisplay = $item->cost_note
                ?: ((float) $item->estimated_cost > 0 ? (float) $item->estimated_cost : '');

            return [
                $index + 1,
                $item->equipment_name,
                $item->brand,
                $item->equipment_code,
                $item->workshop_name,
                $item->breakdown_date?->format('Y-m-d'),
                $item->downtime_days,
                $sizeLabel,
                $item->fault_description,
                $item->workshop_status ?: $item->repair_status?->label(),
                $amountDisplay,
                $item->expected_repair_date_display !== '—' ? $item->expected_repair_date_display : ($item->expected_repair_date instanceof \DateTimeInterface ? $item->expected_repair_date->format('Y-m-d') : (string) ($item->expected_repair_date ?? '')),
                $item->notes,
            ];
        });

        return $this->csvExportService->stream(
            filename: 'equipment-breakdowns-'.now()->format('Y-m-d').'.csv',
            auditableClass: EquipmentBreakdown::class,
            headers: $headers,
            rows: $rows,
            filters: $filters,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(EquipmentBreakdown $breakdown, bool $canViewAuditLogs): array
    {
        $waitingStoppages = $breakdown->driverStoppages()
            ->where('status', DriverStoppageStatus::Waiting->value)
            ->get(['id', 'driver_name', 'employee_id', 'stoppage_start_date', 'status']);

        $linkedDrivers = $waitingStoppages->map(static fn (BreakdownDriverStoppage $stoppage): array => [
            'id' => $stoppage->id,
            'driver_name' => $stoppage->driver_name ?: '—',
            'employee_id' => $stoppage->employee_id ?: '—',
            'idle_days' => $stoppage->idle_days,
        ])->values()->all();

        $sizeLabel = match ($breakdown->severity) {
            BreakdownSeverity::Critical => 'ثقيل (حرج)',
            BreakdownSeverity::Medium => 'متوسط',
            BreakdownSeverity::Minor => 'خفيف',
            default => '—',
        };

        $amountValue = $breakdown->cost_note
            ?: ((float) $breakdown->estimated_cost > 0 ? number_format((float) $breakdown->estimated_cost, 0).' ر.س' : '—');

        return [
            'id' => $breakdown->id,
            'title' => sprintf('%s — %s', $breakdown->equipment_name ?: 'عطل معدة', $breakdown->equipment_code ?: '—'),
            'subtitle' => sprintf('%s • %s', $breakdown->brand ?: '—', $breakdown->workshop_name ?: '—'),
            'status_label' => $breakdown->workshop_status ?: ($breakdown->repair_status?->label() ?? '—'),
            'status_badge' => $breakdown->repair_status?->badgeClasses() ?? 'bg-slate-100 text-slate-700',
            'linked_waiting_stoppages' => $linkedDrivers,
            'fields' => [
                ['label' => 'المعدة', 'value' => $breakdown->equipment_name ?: '—'],
                ['label' => 'النوع', 'value' => $breakdown->brand ?: '—'],
                ['label' => 'الكود', 'value' => $breakdown->equipment_code ?: '—'],
                ['label' => 'الورشة', 'value' => $breakdown->workshop_name ?: '—'],
                ['label' => 'تاريخ التوقف', 'value' => $breakdown->breakdown_date?->format('Y-m-d') ?? '—'],
                ['label' => 'أيام التوقف', 'value' => "{$breakdown->downtime_days} يوم"],
                ['label' => 'حجم العطل', 'value' => $sizeLabel],
                ['label' => 'الموقف', 'value' => $breakdown->workshop_status ?: ($breakdown->repair_status?->label() ?? '—')],
                ['label' => 'المبلغ', 'value' => $amountValue],
                ['label' => 'تاريخ الإصلاح', 'value' => $breakdown->expected_repair_date_display],
                ['label' => 'العطل', 'value' => $breakdown->fault_description ?: '—', 'full' => true],
                ['label' => 'ملاحظات الموقف', 'value' => $breakdown->notes ?: '—', 'full' => true],
            ],
            'record' => [
                'id' => $breakdown->id,
                'equipment_code' => $breakdown->equipment_code ?? '',
                'equipment_name' => $breakdown->equipment_name ?? '',
                'brand' => $breakdown->brand ?? '',
                'workshop_name' => $breakdown->workshop_name ?? '',
                'category' => $breakdown->category?->value ?? 'mechanical',
                'severity' => $breakdown->severity?->value ?? 'medium',
                'breakdown_date' => $breakdown->breakdown_date?->format('Y-m-d') ?? '',
                'workshop_entry_date' => $breakdown->workshop_entry_date?->format('Y-m-d') ?? '',
                'expected_repair_date' => $breakdown->expected_repair_date_display !== '—' ? $breakdown->expected_repair_date_display : (string) ($breakdown->expected_repair_date ?? ''),
                'fault_description' => $breakdown->fault_description ?? '',
                'repair_status' => $breakdown->repair_status?->value ?? 'in_workshop',
                'workshop_status' => $breakdown->workshop_status ?? '',
                'spare_parts_delay' => $breakdown->spare_parts_delay,
                'estimated_cost' => (string) $breakdown->estimated_cost,
                'cost_note' => $breakdown->cost_note ?? '',
                'notes' => $breakdown->notes ?? '',
                'waiting_stoppages_count' => count($linkedDrivers),
                'linked_waiting_stoppages' => $linkedDrivers,
            ],
            'can_view_audit_logs' => $canViewAuditLogs,
            'audit_logs' => $canViewAuditLogs ? $this->auditLogPresenter->forModel($breakdown) : [],
        ];
    }
}
