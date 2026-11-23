<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DriverStoppageStatus;
use App\Models\BreakdownDriverStoppage;
use App\Models\EquipmentBreakdown;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BreakdownDriverStoppageService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<BreakdownDriverStoppage>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = BreakdownDriverStoppage::query()->with('breakdown:id,equipment_code,category,severity,repair_status,breakdown_date');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('driver_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%")
                    ->orWhere('branch', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('equipment_code', 'like', "%{$search}%")
                    ->orWhere('equipment_type', 'like', "%{$search}%")
                    ->orWhere('fault_diagnosis', 'like', "%{$search}%")
                    ->orWhere('workshop_status', 'like', "%{$search}%")
                    ->orWhere('action_taken', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status !== '' && DriverStoppageStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        }

        $branch = trim((string) ($filters['branch'] ?? ''));
        if ($branch !== '') {
            $query->where('branch', $branch);
        }

        $workshopStatus = trim((string) ($filters['workshop_status'] ?? ''));
        if ($workshopStatus !== '') {
            $query->where('workshop_status', $workshopStatus);
        }

        $idleThreshold = (string) ($filters['idle_threshold'] ?? '');
        if ($idleThreshold === 'over_3') {
            $query->active()->whereDate('stoppage_start_date', '<', today()->subDays(3));
        } elseif ($idleThreshold === 'over_7') {
            $query->critical();
        }

        $focus = (string) ($filters['focus'] ?? '');
        if ($focus === 'critical') {
            $query->critical();
        } elseif ($focus === 'active') {
            $query->active();
        }

        $dateFrom = (string) ($filters['date_from'] ?? '');
        if ($dateFrom !== '') {
            $query->whereDate('stoppage_start_date', '>=', $dateFrom);
        }

        $dateTo = (string) ($filters['date_to'] ?? '');
        if ($dateTo !== '') {
            $query->whereDate('stoppage_start_date', '<=', $dateTo);
        }

        return $query
            ->orderByRaw('CASE WHEN stoppage_start_date IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('stoppage_start_date', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, BreakdownDriverStoppage>
     */
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * Normalize equipment code for reliable cross-module matching (e.g. "DT_000020" and "DT-20" -> "DT-20").
     */
    public static function normalizeEquipmentCode(?string $code): string
    {
        $c = strtoupper(trim((string) $code));
        if ($c === '') {
            return '';
        }

        if (preg_match('/^([A-Z]+)[-_0]*([0-9]+)$/', $c, $m)) {
            return $m[1].'-'.(int) $m[2];
        }

        return $c;
    }

    /**
     * Fetch matching EquipmentBreakdown records from "معدات متعطلة بالورش" in a single query (zero N+1).
     *
     * @param  iterable<int, BreakdownDriverStoppage>  $records
     * @return array<string, EquipmentBreakdown>
     */
    public function breakdownsByNormalizedCode(iterable $records): array
    {
        $neededNormalized = [];
        foreach ($records as $stoppage) {
            $norm = self::normalizeEquipmentCode($stoppage->equipment_code);
            if ($norm !== '') {
                $neededNormalized[$norm] = true;
            }
        }

        if (empty($neededNormalized)) {
            return [];
        }

        $allBreakdowns = EquipmentBreakdown::query()
            ->whereNotNull('equipment_code')
            ->where('equipment_code', '!=', '')
            ->get([
                'id',
                'equipment_code',
                'equipment_name',
                'brand',
                'workshop_name',
                'workshop_status',
                'repair_status',
                'fault_description',
                'estimated_cost',
                'cost_note',
                'expected_repair_date',
                'notes',
            ]);

        $map = [];
        foreach ($allBreakdowns as $breakdown) {
            $norm = self::normalizeEquipmentCode($breakdown->equipment_code);
            if ($norm !== '' && isset($neededNormalized[$norm]) && ! isset($map[$norm])) {
                $map[$norm] = $breakdown;
            }
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    public function branches(): array
    {
        return BreakdownDriverStoppage::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch')
            ->all();
    }

    /**
     * @return list<string>
     */
    public function workshopStatuses(): array
    {
        return BreakdownDriverStoppage::query()
            ->whereNotNull('workshop_status')
            ->where('workshop_status', '!=', '')
            ->distinct()
            ->orderBy('workshop_status')
            ->pluck('workshop_status')
            ->all();
    }

    /**
     * @return array{total: int, waiting: int, critical_over_3: int, resolved: int}
     */
    public function kpis(): array
    {
        return [
            'total' => BreakdownDriverStoppage::query()->count(),
            'waiting' => BreakdownDriverStoppage::query()->active()->count(),
            'critical_over_3' => BreakdownDriverStoppage::query()
                ->active()
                ->whereDate('stoppage_start_date', '<', today()->subDays(3))
                ->count(),
            'resolved' => BreakdownDriverStoppage::query()
                ->whereIn('status', [DriverStoppageStatus::Reassigned->value, DriverStoppageStatus::Resolved->value])
                ->count(),
        ];
    }

    /**
     * Active breakdowns eligible for linking in create/edit drawer.
     *
     * @return Collection<int, EquipmentBreakdown>
     */
    public function activeBreakdowns(): Collection
    {
        return EquipmentBreakdown::query()
            ->active()
            ->orderByDesc('breakdown_date')
            ->get(['id', 'equipment_code', 'category', 'severity', 'breakdown_date']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): BreakdownDriverStoppage
    {
        $data = $this->syncEquipmentCodeFromBreakdown($data);

        return BreakdownDriverStoppage::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BreakdownDriverStoppage $stoppage, array $data): BreakdownDriverStoppage
    {
        $data = $this->syncEquipmentCodeFromBreakdown($data);
        $stoppage->update($data);

        return $stoppage->refresh();
    }

    public function delete(BreakdownDriverStoppage $stoppage): void
    {
        $stoppage->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $records = $this->filteredQuery($filters)->get();

        $headers = [
            'م',
            'اسم السائق',
            'الرقم الوظيفي',
            'المهنة',
            'الفرع',
            'كود المعدة',
            'نوع المعدة',
            'آخر تشغيل',
            'عدد أيام التوقف',
            'تشخيص العطل',
            'موقف الورش',
            'الإجراء المتخذ',
            'الحالة',
        ];

        $rows = $records->values()->map(static fn (BreakdownDriverStoppage $item, int $index): array => [
            $index + 1,
            $item->driver_name,
            $item->employee_id,
            $item->job_title,
            $item->branch,
            $item->equipment_code,
            $item->equipment_type,
            $item->stoppage_start_date?->format('Y-m-d'),
            $item->idle_days,
            $item->fault_diagnosis,
            $item->workshop_status,
            $item->action_taken,
            $item->status->label(),
        ]);

        return $this->csvExportService->stream(
            filename: 'breakdown-stoppages-'.now()->format('Y-m-d').'.csv',
            auditableClass: BreakdownDriverStoppage::class,
            headers: $headers,
            rows: $rows,
            filters: $filters,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(BreakdownDriverStoppage $stoppage, bool $canViewAuditLogs): array
    {
        $stoppage->loadMissing('breakdown:id,equipment_code,category,severity,repair_status,breakdown_date');

        $linkedBreakdownLabel = $stoppage->breakdown !== null
            ? sprintf('%s — عطل %s (%s)', $stoppage->breakdown->equipment_code, $stoppage->breakdown->category->label(), $stoppage->breakdown->repair_status->label())
            : 'غير مرتبط بسجل عطل';

        return [
            'id' => $stoppage->id,
            'title' => sprintf('توقف سائق — %s', $stoppage->driver_name ?: '—'),
            'subtitle' => sprintf('%s • %s • %s', $stoppage->employee_id ?: '—', $stoppage->job_title ?: 'سائق', $stoppage->branch ?: '—'),
            'status_label' => $stoppage->workshop_status ?: $stoppage->status->label(),
            'status_badge' => $stoppage->status->badgeClasses(),
            'fields' => [
                ['label' => 'اسم السائق', 'value' => $stoppage->driver_name ?: '—'],
                ['label' => 'الرقم الوظيفي', 'value' => $stoppage->employee_id ?: '—'],
                ['label' => 'المهنة', 'value' => $stoppage->job_title ?: '—'],
                ['label' => 'الفرع', 'value' => $stoppage->branch ?: '—'],
                ['label' => 'كود المعدة', 'value' => $stoppage->equipment_code ?: '—'],
                ['label' => 'نوع المعدة', 'value' => $stoppage->equipment_type ?: '—'],
                ['label' => 'آخر تشغيل / بداية التوقف', 'value' => $stoppage->stoppage_start_date?->format('Y-m-d') ?? '—'],
                ['label' => 'عدد أيام التوقف', 'value' => "{$stoppage->idle_days} يوم"],
                ['label' => 'موقف الورش', 'value' => $stoppage->workshop_status ?: '—'],
                ['label' => 'حالة المتابعة', 'value' => $stoppage->status->label()],
                ['label' => 'تشخيص العطل', 'value' => $stoppage->fault_diagnosis ?: '—', 'full' => true],
                ['label' => 'الإجراء المتخذ', 'value' => $stoppage->action_taken ?: '—', 'full' => true],
                ['label' => 'سجل العطل المرتبط', 'value' => $linkedBreakdownLabel, 'full' => true],
                ['label' => 'ملاحظات إضافية', 'value' => $stoppage->notes ?: '—', 'full' => true],
            ],
            'record' => [
                'id' => $stoppage->id,
                'driver_name' => $stoppage->driver_name ?? '',
                'employee_id' => $stoppage->employee_id ?? '',
                'job_title' => $stoppage->job_title ?? '',
                'branch' => $stoppage->branch ?? '',
                'phone' => $stoppage->phone ?? '',
                'equipment_code' => $stoppage->equipment_code ?? '',
                'equipment_type' => $stoppage->equipment_type ?? '',
                'breakdown_id' => $stoppage->breakdown_id !== null ? (string) $stoppage->breakdown_id : '',
                'stoppage_start_date' => $stoppage->stoppage_start_date?->format('Y-m-d') ?? '',
                'expected_resume_date' => $stoppage->expected_resume_date?->format('Y-m-d') ?? '',
                'fault_diagnosis' => $stoppage->fault_diagnosis ?? '',
                'workshop_status' => $stoppage->workshop_status ?? '',
                'action_taken' => $stoppage->action_taken ?? '',
                'status' => $stoppage->status->value,
                'notes' => $stoppage->notes ?? '',
            ],
            'can_view_audit_logs' => $canViewAuditLogs,
            'audit_logs' => $canViewAuditLogs ? $this->auditLogPresenter->forModel($stoppage) : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function syncEquipmentCodeFromBreakdown(array $data): array
    {
        if (! empty($data['breakdown_id']) && empty($data['equipment_code'])) {
            $breakdown = EquipmentBreakdown::query()->find($data['breakdown_id']);
            if ($breakdown !== null) {
                $data['equipment_code'] = $breakdown->equipment_code;
            }
        }

        return $data;
    }
}
