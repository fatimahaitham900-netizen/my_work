<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RentalEquipment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RentalEquipmentService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<RentalEquipment>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = RentalEquipment::query();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('contractor_name', 'like', "%{$search}%")
                    ->orWhere('equipment_type', 'like', "%{$search}%")
                    ->orWhere('rental_type', 'like', "%{$search}%")
                    ->orWhere('branch', 'like', "%{$search}%")
                    ->orWhere('project', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        foreach (['contractor_name', 'equipment_type', 'rental_type', 'branch', 'project'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));
            if ($value !== '') {
                $query->where($column, $value);
            }
        }

        return $query->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, RentalEquipment>
     */
    public function paginate(array $filters, int $perPage = 100): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredAmountTotal(array $filters): float
    {
        return (float) $this->filteredQuery($filters)->sum('rental_amount');
    }

    /**
     * @return array{
     *     total: int,
     *     total_amount: string,
     *     contractors_count: int,
     *     projects_count: int
     * }
     */
    public function kpis(): array
    {
        return [
            'total' => RentalEquipment::query()->count(),
            'total_amount' => number_format((float) RentalEquipment::query()->sum('rental_amount'), 0),
            'contractors_count' => RentalEquipment::query()
                ->whereNotNull('contractor_name')
                ->where('contractor_name', '!=', '')
                ->distinct('contractor_name')
                ->count('contractor_name'),
            'projects_count' => RentalEquipment::query()
                ->whereNotNull('project')
                ->where('project', '!=', '')
                ->distinct('project')
                ->count('project'),
        ];
    }

    /**
     * @return array{
     *     contractors: list<string>,
     *     equipment_types: list<string>,
     *     rental_types: list<string>,
     *     branches: list<string>,
     *     projects: list<string>
     * }
     */
    public function filterOptions(): array
    {
        $distinct = static fn (string $column): array => RentalEquipment::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return [
            'contractors' => $distinct('contractor_name'),
            'equipment_types' => $distinct('equipment_type'),
            'rental_types' => $distinct('rental_type'),
            'branches' => $distinct('branch'),
            'projects' => $distinct('project'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): RentalEquipment
    {
        $equipment = RentalEquipment::query()->create($data);
        DashboardService::clearCache();

        return $equipment;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(RentalEquipment $equipment, array $data): RentalEquipment
    {
        $equipment->update($data);
        DashboardService::clearCache();

        return $equipment->refresh();
    }

    public function delete(RentalEquipment $equipment): void
    {
        $equipment->delete();
        DashboardService::clearCache();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $records = $this->filteredQuery($filters)->get();

        $headers = [
            'م',
            'اسم المقاول',
            'النوع',
            'مبلغ الإيجار',
            'نوع الإيجار',
            'الفرع',
            'المشروع',
        ];

        $rows = $records->values()->map(static fn (RentalEquipment $item, int $index): array => [
            $index + 1,
            $item->contractor_name,
            $item->equipment_type,
            (float) $item->rental_amount,
            $item->rental_type,
            $item->branch,
            $item->project,
        ]);

        return $this->csvExportService->stream(
            filename: 'rental-equipment-'.now()->format('Y-m-d').'.csv',
            auditableClass: RentalEquipment::class,
            headers: $headers,
            rows: $rows,
            filters: $filters,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(RentalEquipment $equipment, bool $canViewAuditLogs): array
    {
        return [
            'id' => $equipment->id,
            'title' => sprintf('%s — %s', $equipment->equipment_type ?: 'معدة إيجار', $equipment->contractor_name ?: '—'),
            'subtitle' => sprintf('%s ر.س • %s • %s', number_format((float) $equipment->rental_amount, 0), $equipment->rental_type ?: '—', $equipment->branch ?: '—'),
            'status_label' => $equipment->rental_type ?: 'إيجار',
            'status_badge' => 'bg-emerald-50 text-emerald-800 ring-1 ring-inset ring-emerald-600/25',
            'fields' => [
                ['label' => 'اسم المقاول', 'value' => $equipment->contractor_name ?: '—'],
                ['label' => 'النوع', 'value' => $equipment->equipment_type ?: '—'],
                ['label' => 'مبلغ الإيجار', 'value' => number_format((float) $equipment->rental_amount, 0).' ر.س'],
                ['label' => 'نوع الإيجار', 'value' => $equipment->rental_type ?: '—'],
                ['label' => 'الفرع', 'value' => $equipment->branch ?: '—'],
                ['label' => 'المشروع', 'value' => $equipment->project ?: '—'],
                ['label' => 'ملاحظات', 'value' => $equipment->notes ?: '—', 'full' => true],
            ],
            'record' => [
                'id' => $equipment->id,
                'contractor_name' => $equipment->contractor_name ?? '',
                'equipment_type' => $equipment->equipment_type ?? '',
                'rental_amount' => (string) $equipment->rental_amount,
                'rental_type' => $equipment->rental_type ?? '',
                'branch' => $equipment->branch ?? '',
                'project' => $equipment->project ?? '',
                'notes' => $equipment->notes ?? '',
            ],
            'can_view_audit_logs' => $canViewAuditLogs,
            'audit_logs' => $canViewAuditLogs ? $this->auditLogPresenter->forModel($equipment) : [],
        ];
    }
}
