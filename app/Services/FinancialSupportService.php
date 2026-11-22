<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FinancialSupportMonth;
use App\Models\FinancialSupportTransfer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialSupportService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    /**
     * Resolve active month selection ('all' or numeric string '8'..'11').
     *
     * @param  array<string, mixed>  $filters
     */
    public function resolveActiveMonth(array $filters): string
    {
        $raw = trim((string) ($filters['month'] ?? ''));

        if ($raw === 'all') {
            return 'all';
        }

        if ($raw !== '' && ctype_digit($raw)) {
            return $raw;
        }

        $firstMonth = FinancialSupportMonth::query()
            ->orderBy('year')
            ->orderBy('month')
            ->value('month');

        return $firstMonth !== null ? (string) $firstMonth : 'all';
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<FinancialSupportTransfer>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = FinancialSupportTransfer::query();

        $activeMonth = $this->resolveActiveMonth($filters);
        if ($activeMonth !== 'all') {
            $query->where('support_month', (int) $activeMonth);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('equipment_type', 'like', "%{$search}%")
                    ->orWhere('equipment_code', 'like', "%{$search}%")
                    ->orWhere('beneficiary_name', 'like', "%{$search}%")
                    ->orWhere('fault_type', 'like', "%{$search}%")
                    ->orWhere('branch', 'like', "%{$search}%")
                    ->orWhere('case_status', 'like', "%{$search}%")
                    ->orWhere('po_number', 'like', "%{$search}%")
                    ->orWhere('transfer_stage', 'like', "%{$search}%")
                    ->orWhere('repair_status', 'like', "%{$search}%");
            });
        }

        foreach (['branch', 'transfer_stage', 'repair_status'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));
            if ($value !== '') {
                $query->where($column, $value);
            }
        }

        return $query->orderBy('support_month')->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, FinancialSupportTransfer>
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
        return (float) $this->filteredQuery($filters)->sum('amount');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     total: int,
     *     total_amount: string,
     *     under_repair: int,
     *     not_transferred: int
     * }
     */
    public function kpis(array $filters): array
    {
        $baseQuery = FinancialSupportTransfer::query();
        $activeMonth = $this->resolveActiveMonth($filters);

        if ($activeMonth !== 'all') {
            $baseQuery->where('support_month', (int) $activeMonth);
        }

        return [
            'total' => (clone $baseQuery)->count(),
            'total_amount' => number_format((float) (clone $baseQuery)->sum('amount'), 0),
            'under_repair' => (clone $baseQuery)->underRepair()->count(),
            'not_transferred' => (clone $baseQuery)->notTransferred()->count(),
        ];
    }

    /**
     * @return Collection<int, FinancialSupportMonth>
     */
    public function monthSummaries(): Collection
    {
        return FinancialSupportMonth::query()
            ->orderBy('year')
            ->orderBy('month')
            ->get();
    }

    /**
     * Build rich month tabs with transfer counts and totals for the sub-pages bar.
     *
     * @return list<array{
     *     id: int,
     *     month: int,
     *     year: int,
     *     title: string,
     *     transfers_count: int,
     *     transfers_sum: float,
     *     repair_support_amount: float,
     *     custody_amount: float,
     *     total_amount: float,
     *     not_transferred_amount: ?float,
     *     total_equipment_count: int,
     *     repaired_count: int,
     *     under_repair_count: int
     * }>
     */
    public function monthTabs(): array
    {
        $countsByMonth = FinancialSupportTransfer::query()
            ->selectRaw('support_month, COUNT(*) as total_rows, COALESCE(SUM(amount), 0) as total_sum')
            ->groupBy('support_month')
            ->get()
            ->keyBy('support_month');

        return $this->monthSummaries()->map(static function (FinancialSupportMonth $month) use ($countsByMonth): array {
            $stats = $countsByMonth->get($month->month);

            return [
                'id' => $month->id,
                'month' => (int) $month->month,
                'year' => (int) $month->year,
                'title' => $month->title ?: sprintf('شهر %d', (int) $month->month),
                'transfers_count' => (int) ($stats->total_rows ?? 0),
                'transfers_sum' => (float) ($stats->total_sum ?? 0),
                'repair_support_amount' => (float) $month->repair_support_amount,
                'custody_amount' => (float) $month->custody_amount,
                'total_amount' => (float) $month->total_amount,
                'not_transferred_amount' => $month->not_transferred_amount !== null ? (float) $month->not_transferred_amount : null,
                'total_equipment_count' => (int) $month->total_equipment_count,
                'repaired_count' => (int) $month->repaired_count,
                'under_repair_count' => (int) $month->under_repair_count,
            ];
        })->values()->all();
    }

    /**
     * Build structured data for the "موقف الدعم المالي للمعدات 2026" table on the Executive Dashboard.
     *
     * @return array{
     *     title: string,
     *     rows: list<array<string, mixed>>,
     *     totals: array{
     *         repair_support_amount: float,
     *         custody_amount: float,
     *         total_amount: float,
     *         not_transferred_amount: float,
     *         total_equipment_count: int,
     *         repaired_count: int,
     *         under_repair_count: int
     *     }
     * }
     */
    public function dashboardTableSummary(): array
    {
        $tabs = $this->monthTabs();

        $totals = [
            'repair_support_amount' => 0.0,
            'custody_amount' => 0.0,
            'total_amount' => 0.0,
            'not_transferred_amount' => 0.0,
            'total_equipment_count' => 0,
            'repaired_count' => 0,
            'under_repair_count' => 0,
        ];

        foreach ($tabs as $row) {
            $totals['repair_support_amount'] += $row['repair_support_amount'];
            $totals['custody_amount'] += $row['custody_amount'];
            $totals['total_amount'] += $row['total_amount'];
            $totals['not_transferred_amount'] += (float) ($row['not_transferred_amount'] ?? 0);
            $totals['total_equipment_count'] += $row['total_equipment_count'];
            $totals['repaired_count'] += $row['repaired_count'];
            $totals['under_repair_count'] += $row['under_repair_count'];
        }

        return [
            'title' => 'موقف الدعم المالي للمعدات 2026',
            'rows' => $tabs,
            'totals' => $totals,
        ];
    }

    /**
     * @return array{
     *     branches: list<string>,
     *     transfer_stages: list<string>,
     *     repair_statuses: list<string>
     * }
     */
    public function filterOptions(): array
    {
        $distinct = static fn (string $column): array => FinancialSupportTransfer::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return [
            'branches' => $distinct('branch'),
            'transfer_stages' => $distinct('transfer_stage'),
            'repair_statuses' => $distinct('repair_status'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTransfer(array $data): FinancialSupportTransfer
    {
        return FinancialSupportTransfer::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTransfer(FinancialSupportTransfer $transfer, array $data): FinancialSupportTransfer
    {
        $transfer->update($data);

        return $transfer->refresh();
    }

    public function deleteTransfer(FinancialSupportTransfer $transfer): void
    {
        $transfer->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateMonth(FinancialSupportMonth $month, array $data): FinancialSupportMonth
    {
        $month->update($data);

        return $month->refresh();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $activeMonth = $this->resolveActiveMonth($filters);
        $records = $this->filteredQuery($filters)->get();

        $headers = [
            'م',
            'الشهر',
            'نوع المعدة',
            'الكود',
            'المبلغ',
            'اسم المستفيد',
            'نوع العطل',
            'الفرع',
            'الحالة',
            'رقم الـ PO',
            'المرحلة',
            'تاريخ التحويل',
            'تاريخ التوريد',
            'تاريخ الإصلاح',
            'موقف الإصلاح',
        ];

        $rows = $records->values()->map(static fn (FinancialSupportTransfer $item, int $index): array => [
            $index + 1,
            $item->support_month ? sprintf('شهر %d/%d', $item->support_month, $item->support_year ?? 2026) : '',
            $item->equipment_type,
            $item->equipment_code,
            (float) $item->amount,
            $item->beneficiary_name,
            $item->fault_type,
            $item->branch,
            $item->case_status,
            $item->po_number,
            $item->transfer_stage,
            $item->transfer_date,
            $item->supply_date,
            $item->repair_date,
            $item->repair_status,
        ]);

        $suffix = $activeMonth === 'all' ? 'all' : "month-{$activeMonth}";

        return $this->csvExportService->stream(
            filename: "financial-support-{$suffix}-".now()->format('Y-m-d').'.csv',
            auditableClass: FinancialSupportTransfer::class,
            headers: $headers,
            rows: $rows,
            filters: $filters,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(FinancialSupportTransfer $transfer, bool $canViewAuditLogs): array
    {
        $status = (string) ($transfer->repair_status ?? '');
        $badge = match (true) {
            str_contains($status, 'لم يتم') || str_contains($status, 'متعطل') => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
            str_contains(strtoupper($status), 'STOK') || str_contains(strtoupper($status), 'STOCK') => 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20',
            str_contains($status, 'تم') => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
            default => 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-600/20',
        };

        return [
            'id' => $transfer->id,
            'title' => sprintf('%s — %s', $transfer->equipment_type ?: 'دعم مالي', $transfer->equipment_code ?: '—'),
            'subtitle' => sprintf('شهر %d/%d • %s ر.س • %s', (int) ($transfer->support_month ?? 0), (int) ($transfer->support_year ?? 2026), number_format((float) $transfer->amount, 0), $transfer->branch ?: '—'),
            'status_label' => $status !== '' ? $status : ($transfer->transfer_stage ?: 'قيد المتابعة'),
            'status_badge' => $badge,
            'fields' => [
                ['label' => 'الشهر', 'value' => $transfer->support_month ? sprintf('شهر %d / %d', $transfer->support_month, $transfer->support_year ?? 2026) : '—'],
                ['label' => 'نوع المعدة', 'value' => $transfer->equipment_type ?: '—'],
                ['label' => 'الكود', 'value' => $transfer->equipment_code ?: '—'],
                ['label' => 'المبلغ', 'value' => number_format((float) $transfer->amount, 0).' ر.س'],
                ['label' => 'اسم المستفيد', 'value' => $transfer->beneficiary_name ?: '—'],
                ['label' => 'الفرع', 'value' => $transfer->branch ?: '—'],
                ['label' => 'الحالة', 'value' => $transfer->case_status ?: '—'],
                ['label' => 'رقم الـ PO', 'value' => $transfer->po_number ?: '—'],
                ['label' => 'المرحلة', 'value' => $transfer->transfer_stage ?: '—'],
                ['label' => 'تاريخ التحويل', 'value' => $transfer->transfer_date ?: '—'],
                ['label' => 'تاريخ التوريد', 'value' => $transfer->supply_date ?: '—'],
                ['label' => 'تاريخ الإصلاح', 'value' => $transfer->repair_date ?: '—'],
                ['label' => 'نوع العطل', 'value' => $transfer->fault_type ?: '—', 'full' => true],
                ['label' => 'موقف الإصلاح', 'value' => $transfer->repair_status ?: '—', 'full' => true],
            ],
            'record' => [
                'id' => $transfer->id,
                'support_year' => (string) ($transfer->support_year ?? 2026),
                'support_month' => (string) ($transfer->support_month ?? 8),
                'equipment_type' => $transfer->equipment_type ?? '',
                'equipment_code' => $transfer->equipment_code ?? '',
                'amount' => (string) $transfer->amount,
                'beneficiary_name' => $transfer->beneficiary_name ?? '',
                'fault_type' => $transfer->fault_type ?? '',
                'branch' => $transfer->branch ?? '',
                'case_status' => $transfer->case_status ?? '',
                'po_number' => $transfer->po_number ?? '',
                'transfer_stage' => $transfer->transfer_stage ?? '',
                'transfer_date' => $transfer->transfer_date ?? '',
                'supply_date' => $transfer->supply_date ?? '',
                'repair_date' => $transfer->repair_date ?? '',
                'repair_status' => $transfer->repair_status ?? '',
            ],
            'can_view_audit_logs' => $canViewAuditLogs,
            'audit_logs' => $canViewAuditLogs ? $this->auditLogPresenter->forModel($transfer) : [],
        ];
    }
}
