<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OwnershipTransferStatus;
use App\Models\OwnershipTransfer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnershipTransferService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<OwnershipTransfer>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = OwnershipTransfer::query();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('equipment_code', 'like', "%{$search}%")
                    ->orWhere('equipment_type', 'like', "%{$search}%")
                    ->orWhere('branch', 'like', "%{$search}%")
                    ->orWhere('chassis_number', 'like', "%{$search}%")
                    ->orWhere('current_owner', 'like', "%{$search}%")
                    ->orWhere('target_owner', 'like', "%{$search}%")
                    ->orWhere('blocker_reason', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status !== '' && OwnershipTransferStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        }

        $branch = trim((string) ($filters['branch'] ?? ''));
        if ($branch !== '') {
            $query->where('branch', $branch);
        }

        $focus = (string) ($filters['focus'] ?? '');
        if ($focus === 'critical') {
            $query->critical();
        } elseif ($focus === 'active') {
            $query->active();
        }

        $dateFrom = (string) ($filters['date_from'] ?? '');
        if ($dateFrom !== '') {
            $query->whereDate('expected_date', '>=', $dateFrom);
        }

        $dateTo = (string) ($filters['date_to'] ?? '');
        if ($dateTo !== '') {
            $query->whereDate('expected_date', '<=', $dateTo);
        }

        return $query
            ->orderBy('equipment_code')
            ->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, OwnershipTransfer>
     */
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * Distinct branch names for filter dropdown.
     *
     * @return list<string>
     */
    public function branches(): array
    {
        return OwnershipTransfer::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch')
            ->all();
    }

    /**
     * @return array{total: int, delayed: int, pending: int, on_time: int}
     */
    public function kpis(): array
    {
        return [
            'total' => OwnershipTransfer::query()->count(),
            'delayed' => OwnershipTransfer::query()->critical()->count(),
            'pending' => OwnershipTransfer::query()->where('status', OwnershipTransferStatus::Pending->value)->count(),
            'on_time' => OwnershipTransfer::query()->where('status', OwnershipTransferStatus::OnTime->value)->count(),
        ];
    }

    /**
     * Apply smart auto-delayed rule: if actual_date is null and expected_date < today, flag as delayed.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function applySmartStatus(array $data): array
    {
        $actualDate = $data['actual_date'] ?? null;
        $expectedDate = $data['expected_date'] ?? null;

        if (($actualDate === null || $actualDate === '') && is_string($expectedDate) && $expectedDate !== '') {
            try {
                if (Carbon::parse($expectedDate)->startOfDay()->lt(today())) {
                    $data['status'] = OwnershipTransferStatus::Delayed->value;
                }
            } catch (\Throwable) {
                // Let validation report invalid date formats
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): OwnershipTransfer
    {
        return OwnershipTransfer::query()->create(self::applySmartStatus($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(OwnershipTransfer $transfer, array $data): OwnershipTransfer
    {
        $transfer->update(self::applySmartStatus($data));

        return $transfer->refresh();
    }

    public function delete(OwnershipTransfer $transfer): void
    {
        $transfer->delete();
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
            'البيان',
            'الفرع',
            'رقم الهيكل',
            'ملاحظات',
            'الحالة',
            'المالك الحالي',
            'المالك المستلم',
            'تاريخ الطلب',
            'التاريخ المتوقع',
            'تاريخ الإنجاز الفعلي',
        ];

        $rows = $records->values()->map(static fn (OwnershipTransfer $item, int $index): array => [
            $index + 1,
            $item->equipment_code,
            $item->equipment_type,
            $item->branch,
            $item->chassis_number,
            $item->notes ?: $item->blocker_reason,
            $item->status->label(),
            $item->current_owner,
            $item->target_owner,
            $item->request_date?->format('Y-m-d'),
            $item->expected_date?->format('Y-m-d'),
            $item->actual_date?->format('Y-m-d'),
        ]);

        return $this->csvExportService->stream(
            filename: 'ownership-transfers-'.now()->format('Y-m-d').'.csv',
            auditableClass: OwnershipTransfer::class,
            headers: $headers,
            rows: $rows,
            filters: $filters,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(OwnershipTransfer $transfer, bool $canViewAuditLogs): array
    {
        return [
            'id' => $transfer->id,
            'title' => sprintf('نقل ملكية — %s', $transfer->equipment_code ?: '—'),
            'subtitle' => sprintf('%s%s', $transfer->equipment_type ?: '—', $transfer->branch ? ' • '.$transfer->branch : ''),
            'status_label' => $transfer->status->label(),
            'status_badge' => $transfer->status->badgeClasses(),
            'fields' => [
                ['label' => 'كود المعدة', 'value' => $transfer->equipment_code ?: '—'],
                ['label' => 'البيان', 'value' => $transfer->equipment_type ?: '—'],
                ['label' => 'الفرع', 'value' => $transfer->branch ?: '—'],
                ['label' => 'رقم الهيكل', 'value' => $transfer->chassis_number ?: '—'],
                ['label' => 'المالك / الجهة الحالية', 'value' => $transfer->current_owner ?: '—'],
                ['label' => 'المالك / الجهة المستلمة', 'value' => $transfer->target_owner ?: '—'],
                ['label' => 'تاريخ الطلب', 'value' => $transfer->request_date?->format('Y-m-d') ?? '—'],
                ['label' => 'التاريخ المتوقع', 'value' => $transfer->expected_date?->format('Y-m-d') ?? '—'],
                ['label' => 'تاريخ الإنجاز الفعلي', 'value' => $transfer->actual_date?->format('Y-m-d') ?? 'لم يُنجز بعد'],
                ['label' => 'أيام التأخير', 'value' => $transfer->delay_days > 0 ? "{$transfer->delay_days} يوم" : 'لا يوجد تأخير'],
                ['label' => 'سبب التعثر', 'value' => $transfer->blocker_reason ?: '—', 'full' => true],
                ['label' => 'ملاحظات', 'value' => $transfer->notes ?: '—', 'full' => true],
            ],
            'record' => [
                'id' => $transfer->id,
                'equipment_code' => $transfer->equipment_code ?? '',
                'equipment_type' => $transfer->equipment_type ?? '',
                'branch' => $transfer->branch ?? '',
                'chassis_number' => $transfer->chassis_number ?? '',
                'current_owner' => $transfer->current_owner ?? '',
                'target_owner' => $transfer->target_owner ?? '',
                'request_date' => $transfer->request_date?->format('Y-m-d') ?? '',
                'expected_date' => $transfer->expected_date?->format('Y-m-d') ?? '',
                'actual_date' => $transfer->actual_date?->format('Y-m-d') ?? '',
                'status' => $transfer->status->value,
                'blocker_reason' => $transfer->blocker_reason ?? '',
                'notes' => $transfer->notes ?? '',
            ],
            'can_view_audit_logs' => $canViewAuditLogs,
            'audit_logs' => $canViewAuditLogs ? $this->auditLogPresenter->forModel($transfer) : [],
        ];
    }
}
