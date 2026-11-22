<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CarBreakdown;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CarBreakdownService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<CarBreakdown>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = CarBreakdown::query();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('car_code', 'like', "%{$search}%")
                    ->orWhere('car_type', 'like', "%{$search}%")
                    ->orWhere('plate_number', 'like', "%{$search}%")
                    ->orWhere('model_year', 'like', "%{$search}%")
                    ->orWhere('previous_receiver', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%")
                    ->orWhere('fuel_type', 'like', "%{$search}%")
                    ->orWhere('branch', 'like', "%{$search}%")
                    ->orWhere('condition_status', 'like', "%{$search}%");
            });
        }

        foreach (['branch', 'department', 'fuel_type', 'model_year'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));
            if ($value !== '') {
                $query->where($column, $value);
            }
        }

        return $query->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, CarBreakdown>
     */
    public function paginate(array $filters, int $perPage = 100): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @return array{
     *     total: int,
     *     major_mechanical: int,
     *     needs_inspection: int,
     *     branches_count: int
     * }
     */
    public function kpis(): array
    {
        return [
            'total' => CarBreakdown::query()->count(),
            'major_mechanical' => CarBreakdown::query()->majorMechanical()->count(),
            'needs_inspection' => CarBreakdown::query()->needsInspection()->count(),
            'branches_count' => CarBreakdown::query()
                ->whereNotNull('branch')
                ->where('branch', '!=', '')
                ->distinct('branch')
                ->count('branch'),
        ];
    }

    /**
     * @return array{
     *     branches: list<string>,
     *     departments: list<string>,
     *     fuel_types: list<string>,
     *     model_years: list<string>
     * }
     */
    public function filterOptions(): array
    {
        $distinct = static fn (string $column): array => CarBreakdown::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return [
            'branches' => $distinct('branch'),
            'departments' => $distinct('department'),
            'fuel_types' => $distinct('fuel_type'),
            'model_years' => $distinct('model_year'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CarBreakdown
    {
        return CarBreakdown::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CarBreakdown $carBreakdown, array $data): CarBreakdown
    {
        $carBreakdown->update($data);

        return $carBreakdown->refresh();
    }

    public function delete(CarBreakdown $carBreakdown): void
    {
        $carBreakdown->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $records = $this->filteredQuery($filters)->get();

        $headers = [
            'م',
            'كود السيارة',
            'نوع السيارة',
            'اللوحة',
            'موديل',
            'المستلم السابق',
            'الرقم الوظيفي',
            'الإدارة',
            'الوظيفة',
            'نوع الوقود',
            'الفرع',
            'الحالة',
        ];

        $rows = $records->values()->map(static fn (CarBreakdown $item, int $index): array => [
            $index + 1,
            $item->car_code,
            $item->car_type,
            $item->plate_number,
            $item->model_year,
            $item->previous_receiver,
            $item->employee_id,
            $item->department,
            $item->job_title,
            $item->fuel_type,
            $item->branch,
            $item->condition_status,
        ]);

        return $this->csvExportService->stream(
            filename: 'car-breakdowns-'.now()->format('Y-m-d').'.csv',
            auditableClass: CarBreakdown::class,
            headers: $headers,
            rows: $rows,
            filters: $filters,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(CarBreakdown $carBreakdown, bool $canViewAuditLogs): array
    {
        $isMajor = str_contains((string) $carBreakdown->condition_status, 'ماكينة')
            || str_contains((string) $carBreakdown->condition_status, 'شاسية')
            || str_contains((string) $carBreakdown->condition_status, 'بخاخات');

        return [
            'id' => $carBreakdown->id,
            'title' => sprintf('%s — %s', $carBreakdown->car_code ?: 'سيارة', $carBreakdown->car_type ?: '—'),
            'subtitle' => sprintf('اللوحة: %s • الفرع: %s', $carBreakdown->plate_number ?: '—', $carBreakdown->branch ?: '—'),
            'status_label' => $carBreakdown->condition_status ?: 'غير محدد',
            'status_badge' => $isMajor
                ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20'
                : 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-600/20',
            'fields' => [
                ['label' => 'كود السيارة', 'value' => $carBreakdown->car_code ?: '—'],
                ['label' => 'نوع السيارة', 'value' => $carBreakdown->car_type ?: '—'],
                ['label' => 'اللوحة', 'value' => $carBreakdown->plate_number ?: '—'],
                ['label' => 'موديل', 'value' => $carBreakdown->model_year ?: '—'],
                ['label' => 'المستلم السابق', 'value' => $carBreakdown->previous_receiver ?: '—'],
                ['label' => 'الرقم الوظيفي', 'value' => $carBreakdown->employee_id ?: '—'],
                ['label' => 'الإدارة', 'value' => $carBreakdown->department ?: '—'],
                ['label' => 'الوظيفة', 'value' => $carBreakdown->job_title ?: '—'],
                ['label' => 'نوع الوقود', 'value' => $carBreakdown->fuel_type ?: '—'],
                ['label' => 'الفرع', 'value' => $carBreakdown->branch ?: '—'],
                ['label' => 'الحالة', 'value' => $carBreakdown->condition_status ?: '—', 'full' => true],
            ],
            'record' => [
                'id' => $carBreakdown->id,
                'car_code' => $carBreakdown->car_code ?? '',
                'car_type' => $carBreakdown->car_type ?? '',
                'plate_number' => $carBreakdown->plate_number ?? '',
                'model_year' => $carBreakdown->model_year ?? '',
                'previous_receiver' => $carBreakdown->previous_receiver ?? '',
                'employee_id' => $carBreakdown->employee_id ?? '',
                'department' => $carBreakdown->department ?? '',
                'job_title' => $carBreakdown->job_title ?? '',
                'fuel_type' => $carBreakdown->fuel_type ?? '',
                'branch' => $carBreakdown->branch ?? '',
                'condition_status' => $carBreakdown->condition_status ?? '',
            ],
            'can_view_audit_logs' => $canViewAuditLogs,
            'audit_logs' => $canViewAuditLogs ? $this->auditLogPresenter->forModel($carBreakdown) : [],
        ];
    }
}
