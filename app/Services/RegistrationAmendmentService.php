<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RegistrationAmendment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationAmendmentService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<RegistrationAmendment>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = RegistrationAmendment::query();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('equipment_code', 'like', "%{$search}%")
                    ->orWhere('equipment_type', 'like', "%{$search}%")
                    ->orWhere('branch', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        foreach (['equipment_type', 'branch'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));
            if ($value !== '') {
                $query->where($column, $value);
            }
        }

        // Order from largest stoppage days to smallest (oldest stoppage_date first, NULLs last)
        return $query
            ->orderByRaw('CASE WHEN stoppage_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('stoppage_date')
            ->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, RegistrationAmendment>
     */
    public function paginate(array $filters, int $perPage = 100): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @return array{total: int}
     */
    public function kpis(): array
    {
        return [
            'total' => RegistrationAmendment::query()->count(),
        ];
    }

    /**
     * @return array{
     *     equipment_types: list<string>,
     *     branches: list<string>
     * }
     */
    public function filterOptions(): array
    {
        $distinct = static fn (string $column): array => RegistrationAmendment::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return [
            'equipment_types' => $distinct('equipment_type'),
            'branches' => $distinct('branch'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): RegistrationAmendment
    {
        $record = RegistrationAmendment::query()->create($data);
        DashboardService::clearCache();

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(RegistrationAmendment $amendment, array $data): RegistrationAmendment
    {
        $amendment->update($data);
        DashboardService::clearCache();

        return $amendment->refresh();
    }

    public function delete(RegistrationAmendment $amendment): void
    {
        $amendment->delete();
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
            'الكود',
            'نوع المعدة',
            'الفرع',
            'تاريخ التوقف',
            'أيام التوقف',
        ];

        $rows = $records->values()->map(static fn (RegistrationAmendment $item, int $index): array => [
            $index + 1,
            $item->equipment_code,
            $item->equipment_type,
            $item->branch,
            $item->stoppage_date?->format('Y-m-d') ?? '',
            $item->stoppage_days !== null ? $item->stoppage_days : '',
        ]);

        return $this->csvExportService->stream(
            filename: 'registration-amendments-'.now()->format('Y-m-d').'.csv',
            auditableClass: RegistrationAmendment::class,
            headers: $headers,
            rows: $rows,
            filters: $filters,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(RegistrationAmendment $amendment, bool $canViewAuditLogs): array
    {
        $days = $amendment->stoppage_days;

        return [
            'id' => $amendment->id,
            'title' => sprintf('%s — %s', $amendment->equipment_code ?: 'تعديل استمارة', $amendment->equipment_type ?: '—'),
            'subtitle' => sprintf('الفرع: %s • تاريخ التوقف: %s', $amendment->branch ?: '—', $amendment->stoppage_date?->format('d/m/Y') ?? '—'),
            'status_label' => $days !== null ? sprintf('%d يوم توقف', $days) : 'قيد المتابعة',
            'status_badge' => ($days ?? 0) >= 7
                ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20'
                : 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-600/20',
            'fields' => [
                ['label' => 'الكود', 'value' => $amendment->equipment_code ?: '—'],
                ['label' => 'نوع المعدة', 'value' => $amendment->equipment_type ?: '—'],
                ['label' => 'الفرع', 'value' => $amendment->branch ?: '—'],
                ['label' => 'تاريخ التوقف', 'value' => $amendment->stoppage_date?->format('d/m/Y') ?? '—'],
                ['label' => 'أيام التوقف', 'value' => $days !== null ? $days.' يوم' : '—'],
                ['label' => 'ملاحظات', 'value' => $amendment->notes ?: '—', 'full' => true],
            ],
            'record' => [
                'id' => $amendment->id,
                'equipment_code' => $amendment->equipment_code ?? '',
                'equipment_type' => $amendment->equipment_type ?? '',
                'branch' => $amendment->branch ?? '',
                'stoppage_date' => $amendment->stoppage_date?->format('Y-m-d') ?? '',
                'notes' => $amendment->notes ?? '',
            ],
            'can_view_audit_logs' => $canViewAuditLogs,
            'audit_logs' => $canViewAuditLogs ? $this->auditLogPresenter->forModel($amendment) : [],
        ];
    }
}

