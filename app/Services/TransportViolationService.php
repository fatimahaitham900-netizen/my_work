<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TransportViolation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransportViolationService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<TransportViolation>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = TransportViolation::query();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('violation_number', 'like', "%{$search}%")
                    ->orWhere('record_holder', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('violation_description', 'like', "%{$search}%")
                    ->orWhere('objection_status', 'like', "%{$search}%")
                    ->orWhere('plate_number', 'like', "%{$search}%")
                    ->orWhere('equipment_code', 'like', "%{$search}%")
                    ->orWhere('driver_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('liability', 'like', "%{$search}%")
                    ->orWhere('permit_status', 'like', "%{$search}%");
            });
        }

        foreach (['record_holder', 'department', 'city', 'objection_status', 'employee_status'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));
            if ($value !== '') {
                $query->where($column, $value);
            }
        }

        $dateFrom = (string) ($filters['date_from'] ?? '');
        if ($dateFrom !== '') {
            $query->whereDate('violation_date', '>=', $dateFrom);
        }

        $dateTo = (string) ($filters['date_to'] ?? '');
        if ($dateTo !== '') {
            $query->whereDate('violation_date', '<=', $dateTo);
        }

        return $query->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TransportViolation>
     */
    public function paginate(array $filters, int $perPage = 200): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredAmountTotal(array $filters): float
    {
        return (float) $this->filteredQuery($filters)->sum('amount');
    }

    /**
     * @return array{
     *     total: int,
     *     total_amount: string,
     *     pending_objection: int,
     *     rejected_or_missed: int
     * }
     */
    public function kpis(): array
    {
        return [
            'total' => TransportViolation::query()->count(),
            'total_amount' => number_format((float) TransportViolation::query()->sum('amount'), 0),
            'pending_objection' => TransportViolation::query()->pendingObjection()->count(),
            'rejected_or_missed' => TransportViolation::query()->rejectedOrMissed()->count(),
        ];
    }

    /**
     * @return array{
     *     record_holders: list<string>,
     *     departments: list<string>,
     *     cities: list<string>,
     *     objection_statuses: list<string>,
     *     employee_statuses: list<string>
     * }
     */
    public function filterOptions(): array
    {
        $distinct = static fn (string $column): array => TransportViolation::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return [
            'record_holders' => $distinct('record_holder'),
            'departments' => $distinct('department'),
            'cities' => $distinct('city'),
            'objection_statuses' => $distinct('objection_status'),
            'employee_statuses' => $distinct('employee_status'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TransportViolation
    {
        return TransportViolation::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TransportViolation $violation, array $data): TransportViolation
    {
        $violation->update($data);

        return $violation->refresh();
    }

    public function delete(TransportViolation $violation): void
    {
        $violation->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $records = $this->filteredQuery($filters)->get();

        $headers = [
            'م',
            'رقم المخالفة',
            'السجل',
            'التاريخ',
            'المبلغ',
            'الإدارة',
            'مدينة المخالفة',
            'شرح المخالفة',
            'حالة الاعتراض',
            'رقم اللوحة',
            'الكود',
            'السائق',
            'الرقم الوظيفي',
            'حالة الموظف',
            'التحميل',
            'موقف التصريح',
        ];

        $rows = $records->values()->map(static fn (TransportViolation $item, int $index): array => [
            $index + 1,
            $item->violation_number,
            $item->record_holder,
            $item->violation_date?->format('Y-m-d'),
            (float) $item->amount,
            $item->department,
            $item->city,
            $item->violation_description,
            $item->objection_status,
            $item->plate_number,
            $item->equipment_code,
            $item->driver_name,
            $item->employee_id,
            $item->employee_status,
            $item->liability,
            $item->permit_status,
        ]);

        return $this->csvExportService->stream(
            filename: 'transport-violations-'.now()->format('Y-m-d').'.csv',
            auditableClass: TransportViolation::class,
            headers: $headers,
            rows: $rows,
            filters: $filters,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(TransportViolation $violation, bool $canViewAuditLogs): array
    {
        return [
            'id' => $violation->id,
            'title' => sprintf('مخالفة رقم %s', $violation->violation_number ?: '—'),
            'subtitle' => sprintf('%s • %s • %s ر.س', $violation->record_holder ?: '—', $violation->city ?: '—', number_format((float) $violation->amount, 0)),
            'status_label' => $violation->objection_status ?: 'غير محدد',
            'status_badge' => str_contains((string) $violation->objection_status, 'انتظار')
                ? 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-600/20'
                : 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
            'fields' => [
                ['label' => 'رقم المخالفة', 'value' => $violation->violation_number ?: '—'],
                ['label' => 'السجل', 'value' => $violation->record_holder ?: '—'],
                ['label' => 'التاريخ', 'value' => $violation->violation_date?->format('Y-m-d') ?? '—'],
                ['label' => 'المبلغ', 'value' => number_format((float) $violation->amount, 0).' ر.س'],
                ['label' => 'الإدارة', 'value' => $violation->department ?: '—'],
                ['label' => 'مدينة المخالفة', 'value' => $violation->city ?: '—'],
                ['label' => 'رقم اللوحة', 'value' => $violation->plate_number ?: '—'],
                ['label' => 'الكود', 'value' => $violation->equipment_code ?: '—'],
                ['label' => 'السائق', 'value' => $violation->driver_name ?: '—'],
                ['label' => 'الرقم الوظيفي', 'value' => $violation->employee_id ?: '—'],
                ['label' => 'حالة الموظف', 'value' => $violation->employee_status ?: '—'],
                ['label' => 'التحميل', 'value' => $violation->liability ?: '—'],
                ['label' => 'شرح المخالفة', 'value' => $violation->violation_description ?: '—', 'full' => true],
                ['label' => 'حالة الاعتراض', 'value' => $violation->objection_status ?: '—', 'full' => true],
                ['label' => 'موقف التصريح', 'value' => $violation->permit_status ?: '—', 'full' => true],
            ],
            'record' => [
                'id' => $violation->id,
                'violation_number' => $violation->violation_number ?? '',
                'record_holder' => $violation->record_holder ?? '',
                'violation_date' => $violation->violation_date?->format('Y-m-d') ?? '',
                'amount' => (string) $violation->amount,
                'department' => $violation->department ?? '',
                'city' => $violation->city ?? '',
                'violation_description' => $violation->violation_description ?? '',
                'objection_status' => $violation->objection_status ?? '',
                'plate_number' => $violation->plate_number ?? '',
                'equipment_code' => $violation->equipment_code ?? '',
                'driver_name' => $violation->driver_name ?? '',
                'employee_id' => $violation->employee_id ?? '',
                'employee_status' => $violation->employee_status ?? '',
                'liability' => $violation->liability ?? '',
                'permit_status' => $violation->permit_status ?? '',
            ],
            'can_view_audit_logs' => $canViewAuditLogs,
            'audit_logs' => $canViewAuditLogs ? $this->auditLogPresenter->forModel($violation) : [],
        ];
    }
}
