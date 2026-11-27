<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Driver;
use App\Models\EquipmentDistribution;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EquipmentDistributionService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<EquipmentDistribution>
     */
    public function filteredQuery(array $filters): Builder
    {
        $query = EquipmentDistribution::query();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('equipment_code', 'like', "%{$search}%")
                    ->orWhere('equipment_type', 'like', "%{$search}%")
                    ->orWhere('branch', 'like', "%{$search}%")
                    ->orWhere('site_location', 'like', "%{$search}%")
                    ->orWhere('equipment_status', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        foreach (['equipment_type', 'branch', 'site_location', 'equipment_status'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));
            if ($value !== '') {
                $query->where($column, $value);
            }
        }

        return $query->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, EquipmentDistribution>
     */
    public function paginate(array $filters, int $perPage = 200): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @return array{total: int, types_count: int, branches_count: int, sites_count: int}
     */
    public function kpis(): array
    {
        return [
            'total' => EquipmentDistribution::query()->count(),
            'types_count' => EquipmentDistribution::query()
                ->whereNotNull('equipment_type')
                ->where('equipment_type', '!=', '')
                ->distinct('equipment_type')
                ->count('equipment_type'),
            'branches_count' => EquipmentDistribution::query()
                ->whereNotNull('branch')
                ->where('branch', '!=', '')
                ->distinct('branch')
                ->count('branch'),
            'sites_count' => EquipmentDistribution::query()
                ->whereNotNull('site_location')
                ->where('site_location', '!=', '')
                ->distinct('site_location')
                ->count('site_location'),
        ];
    }

    /**
     * @return array{
     *     equipment_types: list<string>,
     *     branches: list<string>,
     *     site_locations: list<string>,
     *     equipment_statuses: list<string>
     * }
     */
    public function filterOptions(): array
    {
        $distinct = static fn (string $column): array => EquipmentDistribution::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return [
            'equipment_types' => $distinct('equipment_type'),
            'branches' => $distinct('branch'),
            'site_locations' => $distinct('site_location'),
            'equipment_statuses' => $distinct('equipment_status'),
        ];
    }

    /**
     * Build two matrices grouped by equipment_type:
     * 1) Equipment Type × Equipment Status (with totals & percentages)
     * 2) Equipment Type × Branch (with totals & percentages)
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     grand_total: int,
     *     statuses: list<string>,
     *     status_rows: list<array{type: string, counts: array<string, int>, total: int, percentage: float}>,
     *     status_totals: array<string, int>,
     *     branches: list<string>,
     *     branch_rows: list<array{type: string, counts: array<string, int>, total: int, percentage: float}>,
     *     branch_totals: array<string, int>
     * }
     */
    public function summaryByType(array $filters = []): array
    {
        $records = $this->filteredQuery($filters)
            ->get(['equipment_type', 'equipment_status', 'branch']);

        $grandTotal = $records->count();
        $statusCountsByType = [];
        $branchCountsByType = [];
        $statusTotals = [];
        $branchTotals = [];
        $typeTotals = [];

        foreach ($records as $record) {
            $type = trim((string) ($record->equipment_type ?? '')) ?: 'غير محدد';
            $status = trim((string) ($record->equipment_status ?? '')) ?: 'غير محدد';
            $branch = trim((string) ($record->branch ?? '')) ?: 'غير محدد';

            $typeTotals[$type] = ($typeTotals[$type] ?? 0) + 1;
            $statusCountsByType[$type][$status] = ($statusCountsByType[$type][$status] ?? 0) + 1;
            $branchCountsByType[$type][$branch] = ($branchCountsByType[$type][$branch] ?? 0) + 1;
            $statusTotals[$status] = ($statusTotals[$status] ?? 0) + 1;
            $branchTotals[$branch] = ($branchTotals[$branch] ?? 0) + 1;
        }

        arsort($typeTotals);
        arsort($statusTotals);
        arsort($branchTotals);

        $statuses = array_keys($statusTotals);
        $branches = array_keys($branchTotals);

        $statusRows = [];
        $branchRows = [];

        foreach ($typeTotals as $type => $total) {
            $pct = $grandTotal > 0 ? round(($total / $grandTotal) * 100, 1) : 0.0;

            $sCounts = [];
            foreach ($statuses as $st) {
                $sCounts[$st] = (int) ($statusCountsByType[$type][$st] ?? 0);
            }
            $statusRows[] = [
                'type' => $type,
                'counts' => $sCounts,
                'total' => $total,
                'percentage' => $pct,
            ];

            $bCounts = [];
            foreach ($branches as $br) {
                $bCounts[$br] = (int) ($branchCountsByType[$type][$br] ?? 0);
            }
            $branchRows[] = [
                'type' => $type,
                'counts' => $bCounts,
                'total' => $total,
                'percentage' => $pct,
            ];
        }

        return [
            'grand_total' => $grandTotal,
            'statuses' => $statuses,
            'status_rows' => $statusRows,
            'status_totals' => $statusTotals,
            'branches' => $branches,
            'branch_rows' => $branchRows,
            'branch_totals' => $branchTotals,
        ];
    }

    /**
     * Build summary grouped by branch then site_location (with status breakdown, branch subtotals, and percentages).
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     grand_total: int,
     *     statuses: list<string>,
     *     status_totals: array<string, int>,
     *     branch_groups: list<array{
     *         branch: string,
     *         total: int,
     *         percentage: float,
     *         status_totals: array<string, int>,
     *         sites: list<array{site: string, branch: string, counts: array<string, int>, total: int, percentage: float}>
     *     }>,
     *     rows: list<array{site: string, branch: string, counts: array<string, int>, total: int, percentage: float}>
     * }
     */
    public function summaryBySite(array $filters = []): array
    {
        $records = $this->filteredQuery($filters)
            ->get(['site_location', 'branch', 'equipment_status']);

        $grandTotal = $records->count();
        $branchTotals = [];
        $branchStatusTotals = [];
        $branchSiteTotals = [];
        $branchSiteStatusCounts = [];
        $statusTotals = [];

        foreach ($records as $record) {
            $branch = trim((string) ($record->branch ?? '')) ?: 'غير محدد';
            $site = trim((string) ($record->site_location ?? '')) ?: 'غير محدد';
            $status = trim((string) ($record->equipment_status ?? '')) ?: 'غير محدد';

            $branchTotals[$branch] = ($branchTotals[$branch] ?? 0) + 1;
            $branchStatusTotals[$branch][$status] = ($branchStatusTotals[$branch][$status] ?? 0) + 1;
            $branchSiteTotals[$branch][$site] = ($branchSiteTotals[$branch][$site] ?? 0) + 1;
            $branchSiteStatusCounts[$branch][$site][$status] = ($branchSiteStatusCounts[$branch][$site][$status] ?? 0) + 1;
            $statusTotals[$status] = ($statusTotals[$status] ?? 0) + 1;
        }

        arsort($branchTotals);
        arsort($statusTotals);

        $statuses = array_keys($statusTotals);
        $branchGroups = [];
        $rows = [];

        foreach ($branchTotals as $branch => $branchTotal) {
            $sitesMap = $branchSiteTotals[$branch] ?? [];
            arsort($sitesMap);

            $siteRows = [];
            foreach ($sitesMap as $site => $siteTotal) {
                $counts = [];
                foreach ($statuses as $st) {
                    $counts[$st] = (int) ($branchSiteStatusCounts[$branch][$site][$st] ?? 0);
                }

                $siteRow = [
                    'site' => $site,
                    'branch' => $branch,
                    'counts' => $counts,
                    'total' => $siteTotal,
                    'percentage' => $grandTotal > 0 ? round(($siteTotal / $grandTotal) * 100, 1) : 0.0,
                ];
                $siteRows[] = $siteRow;
                $rows[] = $siteRow;
            }

            $bStatusTotals = [];
            foreach ($statuses as $st) {
                $bStatusTotals[$st] = (int) ($branchStatusTotals[$branch][$st] ?? 0);
            }

            $branchGroups[] = [
                'branch' => $branch,
                'total' => $branchTotal,
                'percentage' => $grandTotal > 0 ? round(($branchTotal / $grandTotal) * 100, 1) : 0.0,
                'status_totals' => $bStatusTotals,
                'sites' => $siteRows,
            ];
        }

        return [
            'grand_total' => $grandTotal,
            'statuses' => $statuses,
            'status_totals' => $statusTotals,
            'branch_groups' => $branchGroups,
            'rows' => $rows,
        ];
    }

    /**
     * Build combined Equipment & Drivers summary grouped by equipment_type:
     * - إجمالي الشركة (from equipment_distributions)
     * - سائقين على كفالة الشركة (from drivers)
     * - سائقين عمالة خارجية (from drivers)
     * - معدات بدون سائق (from equipment_distributions: not broken & without driver)
     * - المعدات المتعطلة (from equipment_distributions: broken)
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     rows: list<array{
     *         type: string,
     *         company_total: int,
     *         company_drivers: int,
     *         external_drivers: int,
     *         no_driver_count: int,
     *         broken_count: int
     *     }>,
     *     totals: array{
     *         company_total: int,
     *         company_drivers: int,
     *         external_drivers: int,
     *         no_driver_count: int,
     *         broken_count: int
     *     }
     * }
     */
    public function summaryEquipmentAndDrivers(array $filters = []): array
    {
        $distributions = $this->filteredQuery($filters)
            ->get(['equipment_code', 'equipment_type', 'branch', 'site_location', 'equipment_status', 'notes']);

        $byType = [];
        $codeToType = [];

        foreach ($distributions as $record) {
            $type = trim((string) ($record->equipment_type ?? '')) ?: 'غير محدد';
            $code = self::normalizeCode($record->equipment_code);
            if ($code !== '') {
                $codeToType[$code] = $type;
            }

            if (! isset($byType[$type])) {
                $byType[$type] = [
                    'type' => $type,
                    'company_total' => 0,
                    'company_drivers' => 0,
                    'external_drivers' => 0,
                    'no_driver_count' => 0,
                    'broken_count' => 0,
                ];
            }

            $byType[$type]['company_total']++;

            $status = trim((string) ($record->equipment_status ?? ''));
            $notes = trim((string) ($record->notes ?? ''));

            $isBroken = str_contains($status, 'متعطل')
                || str_contains($status, 'عطل')
                || str_contains($status, 'ورشة')
                || str_contains($status, 'ورشه');

            if ($isBroken) {
                $byType[$type]['broken_count']++;
            } else {
                $isNoDriver = str_contains($status, 'بدون سائق')
                    || str_contains($notes, 'بدون سائق');

                if ($isNoDriver) {
                    $byType[$type]['no_driver_count']++;
                }
            }
        }

        // Pull drivers filtered by matching branch / site_location if active,
        // and link each driver's equipment_code to its equipment_type from equipment_distributions.
        $driversQuery = Driver::query();
        foreach (['branch', 'site_location'] as $column) {
            $val = trim((string) ($filters[$column] ?? ''));
            if ($val !== '') {
                $driversQuery->where($column, $val);
            }
        }

        $drivers = $driversQuery->get(['equipment_code', 'sponsor']);

        foreach ($drivers as $driver) {
            $driverCode = self::normalizeCode($driver->equipment_code);
            $driverType = $driverCode !== '' ? ($codeToType[$driverCode] ?? '') : '';

            if ($driverType === '' || ! isset($byType[$driverType])) {
                continue;
            }

            $sponsor = trim((string) ($driver->sponsor ?? ''));
            $isExternal = str_contains($sponsor, 'عمالة')
                || str_contains($sponsor, 'عماله')
                || str_contains($sponsor, 'خارجي')
                || str_contains($sponsor, 'يومي')
                || str_contains($sponsor, 'مقاول')
                || str_contains($sponsor, 'أجير')
                || str_contains($sponsor, 'اجير')
                || str_contains($sponsor, 'إيجار')
                || str_contains($sponsor, 'ايجار');

            if ($isExternal) {
                $byType[$driverType]['external_drivers']++;
            } else {
                $byType[$driverType]['company_drivers']++;
            }
        }

        uasort($byType, static function (array $a, array $b): int {
            $aBottom = self::isBottomEquipmentType($a['type']) ? 1 : 0;
            $bBottom = self::isBottomEquipmentType($b['type']) ? 1 : 0;

            if ($aBottom !== $bBottom) {
                return $aBottom <=> $bBottom;
            }

            return $b['company_total'] <=> $a['company_total'];
        });

        $rows = array_values($byType);
        $totals = [
            'company_total' => 0,
            'company_drivers' => 0,
            'external_drivers' => 0,
            'no_driver_count' => 0,
            'broken_count' => 0,
        ];

        foreach ($rows as $row) {
            $totals['company_total'] += $row['company_total'];
            $totals['company_drivers'] += $row['company_drivers'];
            $totals['external_drivers'] += $row['external_drivers'];
            $totals['no_driver_count'] += $row['no_driver_count'];
            $totals['broken_count'] += $row['broken_count'];
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    private static function normalizeCode(?string $code): string
    {
        if ($code === null) {
            return '';
        }

        return strtoupper(preg_replace('/[\s\-_]+/u', '', trim($code)) ?? '');
    }

    private static function isBottomEquipmentType(string $type): bool
    {
        $normalized = mb_strtolower(trim($type), 'UTF-8');

        foreach (['مولد', 'كمبروسر', 'كمبريسر', 'كمبرسر', 'كومبروسر', 'كومبريسر', 'compressor', 'generator'] as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): EquipmentDistribution
    {
        $record = EquipmentDistribution::query()->create($data);
        DashboardService::clearCache();

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EquipmentDistribution $record, array $data): EquipmentDistribution
    {
        $record->update($data);
        DashboardService::clearCache();

        return $record->refresh();
    }

    public function delete(EquipmentDistribution $record): void
    {
        $record->delete();
        DashboardService::clearCache();
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(EquipmentDistribution $record, bool $includeAudit): array
    {
        return [
            'id' => $record->id,
            'equipment_code' => $record->equipment_code ?? '',
            'equipment_type' => $record->equipment_type ?? '',
            'branch' => $record->branch ?? '',
            'site_location' => $record->site_location ?? '',
            'equipment_status' => $record->equipment_status ?? '',
            'notes' => $record->notes ?? '',
            'audit_logs' => $includeAudit ? $this->auditLogPresenter->forModel($record) : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $headers = [
            'م',
            'كود المعدة',
            'نوع المعدة',
            'الفرع',
            'الموقع',
            'حالة المعدة',
            'ملاحظة',
        ];

        $rows = [];
        $index = 1;
        foreach ($this->filteredQuery($filters)->cursor() as $record) {
            $rows[] = [
                $index++,
                $record->equipment_code ?? '',
                $record->equipment_type ?? '',
                $record->branch ?? '',
                $record->site_location ?? '',
                $record->equipment_status ?? '',
                $record->notes ?? '',
            ];
        }

        return $this->csvExportService->stream(
            'equipment-distributions-'.now()->format('Y-m-d').'.csv',
            EquipmentDistribution::class,
            $headers,
            $rows,
        );
    }
}
