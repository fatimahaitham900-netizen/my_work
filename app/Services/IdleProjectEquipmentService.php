<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IdleProjectEquipment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IdleProjectEquipmentService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<IdleProjectEquipment>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = IdleProjectEquipment::query();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('equipment_code', 'like', "%{$search}%")
                    ->orWhere('equipment_type', 'like', "%{$search}%")
                    ->orWhere('driver_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('branch', 'like', "%{$search}%")
                    ->orWhere('site_location', 'like', "%{$search}%")
                    ->orWhere('equipment_status', 'like', "%{$search}%");
            });
        }

        foreach (['branch', 'site_location', 'equipment_type'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));
            if ($value !== '') {
                $query->where($column, $value);
            }
        }

        $idleBand = (string) ($filters['idle_band'] ?? '');
        if ($idleBand === 'high') {
            $query->where('total_idle_days', '>=', 7);
        } elseif ($idleBand === 'medium') {
            $query->whereBetween('total_idle_days', [4, 6]);
        } elseif ($idleBand === 'low') {
            $query->where('total_idle_days', '<=', 3);
        }

        return $query->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, IdleProjectEquipment>
     */
    public function paginate(array $filters, int $perPage = 100): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredIdleDaysTotal(array $filters): int
    {
        return (int) $this->filteredQuery($filters)->sum('total_idle_days');
    }

    /**
     * @return array{
     *     total: int,
     *     high_idle_days: int,
     *     sum_idle_days: int,
     *     sites_count: int
     * }
     */
    public function kpis(): array
    {
        return [
            'total' => IdleProjectEquipment::query()->count(),
            'high_idle_days' => IdleProjectEquipment::query()->highIdleDays(7)->count(),
            'sum_idle_days' => (int) IdleProjectEquipment::query()->sum('total_idle_days'),
            'sites_count' => IdleProjectEquipment::query()
                ->whereNotNull('site_location')
                ->where('site_location', '!=', '')
                ->distinct('site_location')
                ->count('site_location'),
        ];
    }

    /**
     * @return array{
     *     branches: list<string>,
     *     sites: list<string>,
     *     equipment_types: list<string>
     * }
     */
    public function filterOptions(): array
    {
        $distinct = static fn (string $column): array => IdleProjectEquipment::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return [
            'branches' => $distinct('branch'),
            'sites' => $distinct('site_location'),
            'equipment_types' => $distinct('equipment_type'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): IdleProjectEquipment
    {
        return IdleProjectEquipment::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(IdleProjectEquipment $equipment, array $data): IdleProjectEquipment
    {
        $equipment->update($data);

        return $equipment->refresh();
    }

    public function delete(IdleProjectEquipment $equipment): void
    {
        $equipment->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $records = $this->filteredQuery($filters)->get();

        $headers = [
            'م',
            'كود المعدة',
            'نوع المعدة',
            'اسم السائق',
            'الرقم الوظيفي',
            'الفرع',
            'الموقع',
            'حالة المعدة',
            'إجمالي أيام التوقف',
        ];

        $rows = $records->values()->map(static fn (IdleProjectEquipment $item, int $index): array => [
            $index + 1,
            $item->equipment_code,
            $item->equipment_type,
            $item->driver_name,
            $item->employee_id,
            $item->branch,
            $item->site_location,
            $item->equipment_status,
            (int) $item->total_idle_days,
        ]);

        return $this->csvExportService->stream(
            filename: 'idle-project-equipment-'.now()->format('Y-m-d').'.csv',
            auditableClass: IdleProjectEquipment::class,
            headers: $headers,
            rows: $rows,
            filters: $filters,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(IdleProjectEquipment $equipment, bool $canViewAuditLogs): array
    {
        $days = (int) $equipment->total_idle_days;

        return [
            'id' => $equipment->id,
            'title' => sprintf('%s — %s', $equipment->equipment_code ?: 'معدة', $equipment->equipment_type ?: '—'),
            'subtitle' => sprintf('%s • %s', $equipment->branch ?: '—', $equipment->site_location ?: '—'),
            'status_label' => sprintf('%s (%d يوم)', $equipment->equipment_status ?: 'بدون عمل فعلي', $days),
            'status_badge' => $days >= 7
                ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20'
                : 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-600/20',
            'fields' => [
                ['label' => 'كود المعدة', 'value' => $equipment->equipment_code ?: '—'],
                ['label' => 'نوع المعدة', 'value' => $equipment->equipment_type ?: '—'],
                ['label' => 'اسم السائق', 'value' => $equipment->driver_name ?: '—'],
                ['label' => 'الرقم الوظيفي', 'value' => $equipment->employee_id ?: '—'],
                ['label' => 'الفرع', 'value' => $equipment->branch ?: '—'],
                ['label' => 'الموقع', 'value' => $equipment->site_location ?: '—'],
                ['label' => 'حالة المعدة', 'value' => $equipment->equipment_status ?: '—'],
                ['label' => 'إجمالي أيام التوقف', 'value' => sprintf('%d يوم', $days)],
            ],
            'record' => [
                'id' => $equipment->id,
                'equipment_code' => $equipment->equipment_code ?? '',
                'equipment_type' => $equipment->equipment_type ?? '',
                'driver_name' => $equipment->driver_name ?? '',
                'employee_id' => $equipment->employee_id ?? '',
                'branch' => $equipment->branch ?? '',
                'site_location' => $equipment->site_location ?? '',
                'equipment_status' => $equipment->equipment_status ?? '',
                'total_idle_days' => (string) $days,
            ],
            'can_view_audit_logs' => $canViewAuditLogs,
            'audit_logs' => $canViewAuditLogs ? $this->auditLogPresenter->forModel($equipment) : [],
        ];
    }
}
