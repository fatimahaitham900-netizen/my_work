<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MaintenanceDeviation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaintenanceDeviationService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<MaintenanceDeviation>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = MaintenanceDeviation::query();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('day_number', 'like', "%{$search}%")
                    ->orWhere('status_note', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $rowType = trim((string) ($filters['row_type'] ?? ''));
        if ($rowType !== '') {
            $query->where('row_type', $rowType);
        }

        return $query->orderBy('day_number')->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, MaintenanceDeviation>
     */
    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * Calculate rounded summary percentages matching the official PDF "الإجمالي" row.
     *
     * @return array{
     *     total_days: int,
     *     active_days: int,
     *     daily_maintenance_rate: int,
     *     preventive_maintenance_rate: int,
     *     daily_deviation_rate: int,
     *     preventive_deviation_rate: int
     * }
     */
    public function summaryAverages(): array
    {
        $totalDays = MaintenanceDeviation::query()->count();
        $activeQuery = MaintenanceDeviation::query()->activeDays();
        $activeDays = (clone $activeQuery)->count();

        if ($activeDays === 0) {
            return [
                'total_days' => $totalDays,
                'active_days' => 0,
                'daily_maintenance_rate' => 0,
                'preventive_maintenance_rate' => 0,
                'daily_deviation_rate' => 0,
                'preventive_deviation_rate' => 0,
            ];
        }

        $dailyRate = (int) round((float) (clone $activeQuery)->avg('daily_maintenance_rate'));
        $preventiveRate = (int) round((float) (clone $activeQuery)->avg('preventive_maintenance_rate'));
        $dailyDeviation = (int) round((float) (clone $activeQuery)->avg('daily_deviation_rate'));
        $preventiveDeviation = (int) round((float) (clone $activeQuery)->avg('preventive_deviation_rate'));

        return [
            'total_days' => $totalDays,
            'active_days' => $activeDays,
            'daily_maintenance_rate' => $dailyRate,
            'preventive_maintenance_rate' => $preventiveRate,
            'daily_deviation_rate' => $dailyDeviation,
            'preventive_deviation_rate' => $preventiveDeviation,
        ];
    }

    /**
     * @return array{
     *     total: int,
     *     daily_maintenance_rate: string,
     *     preventive_maintenance_rate: string,
     *     daily_deviation_rate: string,
     *     preventive_deviation_rate: string
     * }
     */
    public function kpis(): array
    {
        $summary = $this->summaryAverages();

        return [
            'total' => $summary['total_days'],
            'daily_maintenance_rate' => $summary['daily_maintenance_rate'].'%',
            'preventive_maintenance_rate' => $summary['preventive_maintenance_rate'].'%',
            'daily_deviation_rate' => $summary['daily_deviation_rate'].'%',
            'preventive_deviation_rate' => $summary['preventive_deviation_rate'].'%',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MaintenanceDeviation
    {
        return MaintenanceDeviation::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MaintenanceDeviation $record, array $data): MaintenanceDeviation
    {
        $record->update($data);

        return $record->refresh();
    }

    public function delete(MaintenanceDeviation $record): void
    {
        $record->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $records = $this->filteredQuery($filters)->get();

        $headers = [
            'التاريخ',
            'نسبة الصيانة اليومية',
            'نسبة الصيانة الوقائية',
            'نسبة انحراف الصيانة اليومية',
            'نسبة انحراف الصيانة الوقائية',
            'ملاحظات',
        ];

        $rows = $records->map(static function (MaintenanceDeviation $item): array {
            if ($item->row_type === 'on_leave') {
                return [
                    $item->day_number,
                    '',
                    '',
                    '',
                    $item->status_note ?: 'مسؤول الصيانة مجاز',
                    $item->notes,
                ];
            }

            if ($item->row_type === 'day_off') {
                return [
                    $item->day_number,
                    '',
                    '',
                    '',
                    $item->status_note ?: '',
                    $item->notes,
                ];
            }

            return [
                $item->day_number,
                $item->daily_maintenance_rate !== null ? ((int) round((float) $item->daily_maintenance_rate)).'%' : '',
                $item->preventive_maintenance_rate !== null ? ((int) round((float) $item->preventive_maintenance_rate)).'%' : '',
                $item->daily_deviation_rate !== null ? ((int) round((float) $item->daily_deviation_rate)).'%' : '',
                $item->preventive_deviation_rate !== null ? ((int) round((float) $item->preventive_deviation_rate)).'%' : '',
                $item->notes,
            ];
        });

        return $this->csvExportService->stream(
            filename: 'maintenance-deviations-'.now()->format('Y-m-d').'.csv',
            auditableClass: MaintenanceDeviation::class,
            headers: $headers,
            rows: $rows,
            filters: $filters,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(MaintenanceDeviation $record, bool $canViewAuditLogs): array
    {
        $typeLabel = match ($record->row_type) {
            'on_leave' => 'مسؤول الصيانة مجاز',
            'day_off' => 'يوم توقف / إجازة',
            default => 'يوم عمل مسجّل',
        };

        return [
            'id' => $record->id,
            'title' => sprintf('التاريخ: %s', $record->day_number ?? '—'),
            'subtitle' => $typeLabel,
            'status_label' => $typeLabel,
            'status_badge' => $record->row_type === 'active'
                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20'
                : 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-600/20',
            'fields' => [
                ['label' => 'التاريخ (اليوم)', 'value' => (string) ($record->day_number ?? '—')],
                ['label' => 'نوع اليوم', 'value' => $typeLabel],
                ['label' => 'نسبة الصيانة اليومية', 'value' => $record->daily_maintenance_rate !== null ? ((int) round((float) $record->daily_maintenance_rate)).'%' : '—'],
                ['label' => 'نسبة الصيانة الوقائية', 'value' => $record->preventive_maintenance_rate !== null ? ((int) round((float) $record->preventive_maintenance_rate)).'%' : '—'],
                ['label' => 'نسبة انحراف الصيانة اليومية', 'value' => $record->daily_deviation_rate !== null ? ((int) round((float) $record->daily_deviation_rate)).'%' : '—'],
                ['label' => 'نسبة انحراف الصيانة الوقائية', 'value' => $record->preventive_deviation_rate !== null ? ((int) round((float) $record->preventive_deviation_rate)).'%' : ($record->status_note ?: '—')],
                ['label' => 'ملاحظات', 'value' => $record->notes ?: '—', 'full' => true],
            ],
            'record' => [
                'id' => $record->id,
                'day_number' => $record->day_number !== null ? (string) $record->day_number : '',
                'row_type' => $record->row_type ?: 'active',
                'daily_maintenance_rate' => $record->daily_maintenance_rate !== null ? (string) ((int) round((float) $record->daily_maintenance_rate)) : '99',
                'preventive_maintenance_rate' => $record->preventive_maintenance_rate !== null ? (string) ((int) round((float) $record->preventive_maintenance_rate)) : '70',
                'daily_deviation_rate' => $record->daily_deviation_rate !== null ? (string) ((int) round((float) $record->daily_deviation_rate)) : '1',
                'preventive_deviation_rate' => $record->preventive_deviation_rate !== null ? (string) ((int) round((float) $record->preventive_deviation_rate)) : '30',
                'status_note' => $record->status_note ?? '',
                'notes' => $record->notes ?? '',
            ],
            'can_view_audit_logs' => $canViewAuditLogs,
            'audit_logs' => $canViewAuditLogs ? $this->auditLogPresenter->forModel($record) : [],
        ];
    }
}
