<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UnassignedDriverStatus;
use App\Models\UnassignedDriver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnassignedDriverService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<UnassignedDriver>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = UnassignedDriver::query();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('driver_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('license_type', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status !== '' && UnassignedDriverStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        }

        $location = trim((string) ($filters['location'] ?? ''));
        if ($location !== '') {
            $query->where('location', $location);
        }

        $licenseState = (string) ($filters['license_state'] ?? '');
        if ($licenseState === 'expired') {
            $query->whereDate('license_expiry', '<', today());
        } elseif ($licenseState === 'expiring_soon') {
            $query->whereDate('license_expiry', '>=', today())
                ->whereDate('license_expiry', '<=', today()->addDays(30));
        } elseif ($licenseState === 'valid') {
            $query->whereDate('license_expiry', '>', today()->addDays(30));
        }

        $focus = (string) ($filters['focus'] ?? '');
        if ($focus === 'critical') {
            $query->critical();
        } elseif ($focus === 'active') {
            $query->active();
        }

        $dateFrom = (string) ($filters['date_from'] ?? '');
        if ($dateFrom !== '') {
            $query->whereDate('unassigned_since', '>=', $dateFrom);
        }

        $dateTo = (string) ($filters['date_to'] ?? '');
        if ($dateTo !== '') {
            $query->whereDate('unassigned_since', '<=', $dateTo);
        }

        return $query->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, UnassignedDriver>
     */
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @return list<string>
     */
    public function locations(): array
    {
        return UnassignedDriver::query()
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->orderBy('location')
            ->pluck('location')
            ->all();
    }

    /**
     * @return array{total: int, ready: int, license_alerts: int, other: int}
     */
    public function kpis(): array
    {
        return [
            'total' => UnassignedDriver::query()->count(),
            'ready' => UnassignedDriver::query()->where('status', UnassignedDriverStatus::Ready->value)->count(),
            'license_alerts' => UnassignedDriver::query()
                ->whereDate('license_expiry', '<=', today()->addDays(30))
                ->count(),
            'other' => UnassignedDriver::query()
                ->whereIn('status', [UnassignedDriverStatus::OnLeave->value, UnassignedDriverStatus::Evaluation->value])
                ->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): UnassignedDriver
    {
        return UnassignedDriver::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(UnassignedDriver $driver, array $data): UnassignedDriver
    {
        $driver->update($data);

        return $driver->refresh();
    }

    public function updateStatus(UnassignedDriver $driver, UnassignedDriverStatus $status): UnassignedDriver
    {
        $driver->update(['status' => $status]);

        return $driver->refresh();
    }

    public function delete(UnassignedDriver $driver): void
    {
        $driver->delete();
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
            'المهنة',
            'الرقم الوظيفي',
            'الموقع',
            'ملاحظات',
            'تاريخ التوقف',
            'إجمالي أيام التوقف',
            'نوع الرخصة',
            'انتهاء الرخصة',
            'الحالة',
        ];

        $rows = $records->values()->map(static fn (UnassignedDriver $item, int $index): array => [
            $index + 1,
            $item->driver_name,
            $item->job_title,
            $item->employee_id,
            $item->location,
            $item->notes,
            $item->unassigned_since?->format('Y-m-d'),
            $item->idle_duration_days,
            $item->license_type,
            $item->license_expiry?->format('Y-m-d'),
            $item->status->label(),
        ]);

        return $this->csvExportService->stream(
            filename: 'unassigned-drivers-'.now()->format('Y-m-d').'.csv',
            auditableClass: UnassignedDriver::class,
            headers: $headers,
            rows: $rows,
            filters: $filters,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(UnassignedDriver $driver, bool $canViewAuditLogs): array
    {
        $licenseStatus = $driver->is_license_expired
            ? 'منتهية الصلاحية'
            : ($driver->is_license_expiring_soon ? sprintf('قاربت على الانتهاء (متبقي %d يوم)', (int) $driver->license_days_remaining) : 'سارية');

        return [
            'id' => $driver->id,
            'title' => sprintf('سائق بدون معدة — %s', $driver->driver_name ?: '—'),
            'subtitle' => sprintf('%s • %s • %s', $driver->employee_id ?: '—', $driver->job_title ?: ($driver->license_type ?: 'سائق'), $driver->location ?: '—'),
            'status_label' => $driver->status->label(),
            'status_badge' => $driver->status->badgeClasses(),
            'fields' => [
                ['label' => 'اسم السائق', 'value' => $driver->driver_name ?: '—'],
                ['label' => 'المهنة', 'value' => $driver->job_title ?: '—'],
                ['label' => 'الرقم الوظيفي', 'value' => $driver->employee_id ?: '—'],
                ['label' => 'الموقع', 'value' => $driver->location ?: '—'],
                ['label' => 'نوع الرخصة', 'value' => $driver->license_type ?: '—'],
                ['label' => 'تاريخ انتهاء الرخصة', 'value' => $driver->license_expiry ? sprintf('%s (%s)', $driver->license_expiry->format('Y-m-d'), $licenseStatus) : '—'],
                ['label' => 'تاريخ التوقف', 'value' => $driver->unassigned_since?->format('Y-m-d') ?? '—'],
                ['label' => 'إجمالي أيام التوقف', 'value' => "{$driver->idle_duration_days} يوم"],
                ['label' => 'ملاحظات', 'value' => $driver->notes ?: '—', 'full' => true],
            ],
            'record' => [
                'id' => $driver->id,
                'driver_name' => $driver->driver_name ?? '',
                'employee_id' => $driver->employee_id ?? '',
                'job_title' => $driver->job_title ?? '',
                'location' => $driver->location ?? '',
                'license_type' => $driver->license_type ?? '',
                'license_expiry' => $driver->license_expiry?->format('Y-m-d') ?? '',
                'unassigned_since' => $driver->unassigned_since?->format('Y-m-d') ?? '',
                'status' => $driver->status->value,
                'notes' => $driver->notes ?? '',
            ],
            'can_view_audit_logs' => $canViewAuditLogs,
            'audit_logs' => $canViewAuditLogs ? $this->auditLogPresenter->forModel($driver) : [],
        ];
    }
}
