<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Driver;
use App\Models\EquipmentDistribution;
use App\Support\ExtraColumns;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriverService
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly AuditLogPresenter $auditLogPresenter,
    ) {}

    public static function normalizeEquipmentCode(?string $code): string
    {
        $clean = strtoupper(trim((string) $code));
        if ($clean === '') {
            return '';
        }

        return preg_replace('/[\s\-]+/', '_', $clean) ?? $clean;
    }

    /**
     * Common nationality spelling variants mapped to a single canonical label.
     *
     * @var array<string, string>
     */
    private const NATIONALITY_ALIASES = [
        'sodan' => 'Sudan',
        'sudan' => 'Sudan',
        'sudanese' => 'Sudan',
        'السودان' => 'Sudan',
        'سوداني' => 'Sudan',
        'india' => 'India',
        'indian' => 'India',
        'الهند' => 'India',
        'هندي' => 'India',
        'yemen' => 'Yemen',
        'yemeni' => 'Yemen',
        'اليمن' => 'Yemen',
        'يمني' => 'Yemen',
        'pakistan' => 'Pakistan',
        'pakistani' => 'Pakistan',
        'باكستان' => 'Pakistan',
        'باكستاني' => 'Pakistan',
        'philippines' => 'Philippines',
        'philipines' => 'Philippines',
        'phillippines' => 'Philippines',
        'filipino' => 'Philippines',
        'الفلبين' => 'Philippines',
        'فلبيني' => 'Philippines',
        'nepal' => 'Nepal',
        'nepali' => 'Nepal',
        'nepalese' => 'Nepal',
        'نيبال' => 'Nepal',
        'نيبالي' => 'Nepal',
        'bangladesh' => 'Bangladesh',
        'bangladeshi' => 'Bangladesh',
        'بنجلاديش' => 'Bangladesh',
        'بنغلاديش' => 'Bangladesh',
        'saudi arabia' => 'Saudi Arabia',
        'saudi' => 'Saudi Arabia',
        'ksa' => 'Saudi Arabia',
        'السعودية' => 'Saudi Arabia',
        'سعودي' => 'Saudi Arabia',
        'egypt' => 'Egypt',
        'egyptian' => 'Egypt',
        'مصر' => 'Egypt',
        'مصري' => 'Egypt',
    ];

    public static function normalizeNationality(?string $nationality): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', (string) $nationality) ?? '');
        if ($clean === '') {
            return 'غير محدد';
        }

        $lower = mb_strtolower($clean, 'UTF-8');
        if (isset(self::NATIONALITY_ALIASES[$lower])) {
            return self::NATIONALITY_ALIASES[$lower];
        }

        return mb_convert_case($lower, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Return all lowercase spelling variants that normalize to the given nationality.
     *
     * @return list<string>
     */
    public static function nationalityVariants(string $nationality): array
    {
        $canonical = self::normalizeNationality($nationality);
        $variants = [mb_strtolower($canonical, 'UTF-8'), mb_strtolower(trim($nationality), 'UTF-8')];

        foreach (self::NATIONALITY_ALIASES as $alias => $target) {
            if ($target === $canonical) {
                $variants[] = $alias;
            }
        }

        return array_values(array_unique($variants));
    }

    /**
     * Single-query lookup map from normalized equipment_code => [equipment_type, branch, site_location] from توزيعة المعدات.
     *
     * @return array<string, array{equipment_type: string, branch: string, site_location: string}>
     */
    public function distributionLookupMap(): array
    {
        $map = [];

        $distributions = EquipmentDistribution::query()
            ->whereNotNull('equipment_code')
            ->where('equipment_code', '!=', '')
            ->get(['equipment_code', 'equipment_type', 'branch', 'site_location']);

        foreach ($distributions as $dist) {
            $key = self::normalizeEquipmentCode($dist->equipment_code);
            if ($key === '') {
                continue;
            }

            $map[$key] = [
                'equipment_type' => trim((string) ($dist->equipment_type ?? '')),
                'branch' => trim((string) ($dist->branch ?? '')),
                'site_location' => trim((string) ($dist->site_location ?? '')),
            ];
        }

        return $map;
    }

    /**
     * Sync stored branch, site_location, and exact imported equipment_type/branch/site on drivers from توزيعة المعدات.
     */
    public function syncBranchAndSiteFromDistributions(): int
    {
        $distMap = $this->distributionLookupMap();
        if (empty($distMap)) {
            return 0;
        }

        $updated = 0;

        foreach (Driver::query()->where('staff_category', Driver::CATEGORY_DRIVER)->whereNotNull('equipment_code')->where('equipment_code', '!=', '')->cursor() as $driver) {
            $key = self::normalizeEquipmentCode($driver->equipment_code);
            if ($key === '' || ! isset($distMap[$key])) {
                continue;
            }

            $distType = $distMap[$key]['equipment_type'];
            $distBranch = $distMap[$key]['branch'];
            $distSite = $distMap[$key]['site_location'];
            $dirty = false;

            if ($distBranch !== '' && $driver->branch !== $distBranch) {
                $driver->branch = $distBranch;
                $dirty = true;
            }
            if ($distSite !== '' && $driver->site_location !== $distSite) {
                $driver->site_location = $distSite;
                $dirty = true;
            }

            $extra = $driver->extra_columns;
            if (is_array($extra) && isset($extra[ExtraColumns::EXACT_KEY]) && is_array($extra[ExtraColumns::EXACT_KEY])) {
                $exact = $extra[ExtraColumns::EXACT_KEY];
                foreach (array_keys($exact) as $header) {
                    $cleanHeader = trim((string) $header);
                    if ($distType !== '' && in_array($cleanHeader, ['نوع المعدة', 'نوع المعده', 'المعدة', 'المعده', 'النوع', 'equipment_type'], true)) {
                        if ($exact[$header] !== $distType) {
                            $exact[$header] = $distType;
                            $dirty = true;
                        }
                    } elseif ($distBranch !== '' && in_array($cleanHeader, ['الفرع', 'الفرع الحالي', 'branch'], true)) {
                        if ($exact[$header] !== $distBranch) {
                            $exact[$header] = $distBranch;
                            $dirty = true;
                        }
                    } elseif ($distSite !== '' && in_array($cleanHeader, ['الموقع', 'المشروع', 'المشروع / الموقع', 'site_location', 'location'], true)) {
                        if ($exact[$header] !== $distSite) {
                            $exact[$header] = $distSite;
                            $dirty = true;
                        }
                    }
                }
                $extra[ExtraColumns::EXACT_KEY] = $exact;
                $driver->extra_columns = $extra;
            }

            if ($dirty) {
                $driver->save();
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Live-apply linked equipment_type, branch, and site_location onto a driver model for display without extra DB queries.
     *
     * @param  array<string, array{equipment_type: string, branch: string, site_location: string}>  $distMap
     */
    public function applyLiveDistribution(Driver $driver, array $distMap): Driver
    {
        $key = self::normalizeEquipmentCode($driver->equipment_code);
        if ($key === '' || ! isset($distMap[$key])) {
            return $driver;
        }

        $distType = $distMap[$key]['equipment_type'];
        $distBranch = $distMap[$key]['branch'];
        $distSite = $distMap[$key]['site_location'];

        if ($distBranch !== '') {
            $driver->branch = $distBranch;
        }
        if ($distSite !== '') {
            $driver->site_location = $distSite;
        }

        $extra = $driver->extra_columns;
        if (is_array($extra) && isset($extra[ExtraColumns::EXACT_KEY]) && is_array($extra[ExtraColumns::EXACT_KEY])) {
            $exact = $extra[ExtraColumns::EXACT_KEY];
            foreach (array_keys($exact) as $header) {
                $cleanHeader = trim((string) $header);
                if ($distType !== '' && in_array($cleanHeader, ['نوع المعدة', 'نوع المعده', 'المعدة', 'المعده', 'النوع', 'equipment_type'], true)) {
                    $exact[$header] = $distType;
                } elseif ($distBranch !== '' && in_array($cleanHeader, ['الفرع', 'الفرع الحالي', 'branch'], true)) {
                    $exact[$header] = $distBranch;
                } elseif ($distSite !== '' && in_array($cleanHeader, ['الموقع', 'المشروع', 'المشروع / الموقع', 'site_location', 'location'], true)) {
                    $exact[$header] = $distSite;
                }
            }
            $extra[ExtraColumns::EXACT_KEY] = $exact;
            $driver->extra_columns = $extra;
        }

        return $driver;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Driver>
     */
    public function filteredQuery(array $filters): Builder
    {
        $category = ($filters['view'] ?? '') === 'workshop_staff' || ($filters['staff_category'] ?? '') === Driver::CATEGORY_WORKSHOP_STAFF
            ? Driver::CATEGORY_WORKSHOP_STAFF
            : Driver::CATEGORY_DRIVER;

        $query = Driver::query()->where('staff_category', $category);

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('equipment_code', 'like', "%{$search}%")
                    ->orWhere('branch', 'like', "%{$search}%")
                    ->orWhere('site_location', 'like', "%{$search}%")
                    ->orWhere('driver_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%")
                    ->orWhere('nationality', 'like', "%{$search}%")
                    ->orWhere('sponsor', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        foreach (['branch', 'site_location', 'job_title', 'sponsor', 'status'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));
            if ($value !== '') {
                $query->where($column, $value);
            }
        }

        $nationality = trim((string) ($filters['nationality'] ?? ''));
        if ($nationality !== '') {
            if ($nationality === 'غير محدد') {
                $query->where(function (Builder $sub): void {
                    $sub->whereNull('nationality')->orWhere('nationality', '');
                });
            } else {
                $variants = self::nationalityVariants($nationality);
                $placeholders = implode(', ', array_fill(0, count($variants), '?'));
                $query->whereRaw("LOWER(TRIM(nationality)) IN ({$placeholders})", $variants);
            }
        }

        $equipmentType = trim((string) ($filters['equipment_type'] ?? ''));
        if ($equipmentType !== '') {
            $distMap = $this->distributionLookupMap();
            $matchingNormCodes = [];
            foreach ($distMap as $normCode => $info) {
                if ($info['equipment_type'] === $equipmentType) {
                    $matchingNormCodes[$normCode] = true;
                }
            }

            $allDrivers = Driver::query()->get(['id', 'equipment_code']);
            $matchingIds = [];
            foreach ($allDrivers as $drv) {
                $norm = self::normalizeEquipmentCode($drv->equipment_code);
                $drvType = ($norm !== '' && isset($distMap[$norm]) && $distMap[$norm]['equipment_type'] !== '')
                    ? $distMap[$norm]['equipment_type']
                    : 'غير محدد';
                if ($drvType === $equipmentType) {
                    $matchingIds[] = $drv->id;
                }
            }

            if (empty($matchingIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $matchingIds);
            }
        }

        return $query->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Driver>
     */
    public function paginate(array $filters, int $perPage = 200): LengthAwarePaginator
    {
        $paginator = $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
        $distMap = $this->distributionLookupMap();

        if (! empty($distMap)) {
            $paginator->getCollection()->transform(fn (Driver $driver): Driver => $this->applyLiveDistribution($driver, $distMap));
        }

        return $paginator;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredSalaryTotal(array $filters): float
    {
        return (float) $this->filteredQuery($filters)->sum('salary');
    }

    /**
     * @return array{
     *     total: int,
     *     total_salary: string,
     *     jobs_count: int,
     *     nationalities_count: int,
     *     workshop_staff_total: int,
     *     workshop_staff_salary: string,
     *     workshop_staff_active: int,
     *     workshop_branches_count: int
     * }
     */
    public function kpis(): array
    {
        $driversBase = Driver::query()->where('staff_category', Driver::CATEGORY_DRIVER);
        $workshopBase = Driver::query()->where('staff_category', Driver::CATEGORY_WORKSHOP_STAFF);

        return [
            'total' => (clone $driversBase)->count(),
            'total_salary' => number_format((float) (clone $driversBase)->sum('salary'), 0),
            'jobs_count' => (clone $driversBase)
                ->whereNotNull('job_title')
                ->where('job_title', '!=', '')
                ->distinct('job_title')
                ->count('job_title'),
            'nationalities_count' => (clone $driversBase)
                ->whereNotNull('nationality')
                ->where('nationality', '!=', '')
                ->pluck('nationality')
                ->map(static fn (string $n): string => self::normalizeNationality($n))
                ->unique()
                ->count(),
            'workshop_staff_total' => (clone $workshopBase)->count(),
            'workshop_staff_salary' => number_format((float) (clone $workshopBase)->sum('salary'), 0),
            'workshop_staff_active' => (clone $workshopBase)
                ->where(function (Builder $q): void {
                    $q->whereNull('status')->orWhere('status', 'like', '%نشط%');
                })
                ->count(),
            'workshop_branches_count' => (clone $workshopBase)
                ->whereNotNull('branch')
                ->where('branch', '!=', '')
                ->distinct('branch')
                ->count('branch'),
        ];
    }

    /**
     * @return array{
     *     branches: list<string>,
     *     site_locations: list<string>,
     *     job_titles: list<string>,
     *     nationalities: list<string>,
     *     sponsors: list<string>,
     *     statuses: list<string>
     * }
     */
    public function filterOptions(string $category = Driver::CATEGORY_DRIVER): array
    {
        $distinct = static fn (string $column): array => Driver::query()
            ->where('staff_category', $category)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        $nationalities = Driver::query()
            ->where('staff_category', $category)
            ->whereNotNull('nationality')
            ->where('nationality', '!=', '')
            ->pluck('nationality')
            ->map(static fn (string $n): string => self::normalizeNationality($n))
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'branches' => $distinct('branch'),
            'site_locations' => $distinct('site_location'),
            'job_titles' => $distinct('job_title'),
            'nationalities' => $nationalities,
            'sponsors' => $distinct('sponsor'),
            'statuses' => $distinct('status'),
        ];
    }

    /**
     * Build executive summary matrices for السائقين (Job × Branch, Nationality, Sponsor).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters = []): array
    {
        $distMap = $this->distributionLookupMap();
        $drivers = $this->filteredQuery($filters)
            ->get(['equipment_code', 'branch', 'site_location', 'job_title', 'nationality', 'salary', 'sponsor']);

        $grandTotal = $drivers->count();
        $totalSalary = 0.0;

        $jobTotals = [];
        $jobSalaries = [];
        $jobBranchCounts = [];
        $equipmentTypeTotals = [];
        $equipmentTypeSalaries = [];
        $equipmentTypeBranchCounts = [];
        $branchTotals = [];

        $nationalityTotals = [];
        $nationalitySalaries = [];

        $sponsorTotals = [];
        $sponsorSalaries = [];

        foreach ($drivers as $driver) {
            $key = self::normalizeEquipmentCode($driver->equipment_code);
            $resolvedBranch = ($key !== '' && isset($distMap[$key]) && $distMap[$key]['branch'] !== '')
                ? $distMap[$key]['branch']
                : trim((string) ($driver->branch ?? ''));
            $resolvedType = ($key !== '' && isset($distMap[$key]) && $distMap[$key]['equipment_type'] !== '')
                ? $distMap[$key]['equipment_type']
                : 'غير محدد';

            $branch = $resolvedBranch !== '' ? $resolvedBranch : 'غير محدد';
            $job = trim((string) ($driver->job_title ?? '')) ?: 'غير محدد';
            $nat = self::normalizeNationality($driver->nationality);
            $sponsor = trim((string) ($driver->sponsor ?? '')) ?: 'غير محدد';
            $salary = (float) $driver->salary;

            $totalSalary += $salary;

            $jobTotals[$job] = ($jobTotals[$job] ?? 0) + 1;
            $jobSalaries[$job] = ($jobSalaries[$job] ?? 0.0) + $salary;
            $jobBranchCounts[$job][$branch] = ($jobBranchCounts[$job][$branch] ?? 0) + 1;

            $equipmentTypeTotals[$resolvedType] = ($equipmentTypeTotals[$resolvedType] ?? 0) + 1;
            $equipmentTypeSalaries[$resolvedType] = ($equipmentTypeSalaries[$resolvedType] ?? 0.0) + $salary;
            $equipmentTypeBranchCounts[$resolvedType][$branch] = ($equipmentTypeBranchCounts[$resolvedType][$branch] ?? 0) + 1;

            $branchTotals[$branch] = ($branchTotals[$branch] ?? 0) + 1;

            $nationalityTotals[$nat] = ($nationalityTotals[$nat] ?? 0) + 1;
            $nationalitySalaries[$nat] = ($nationalitySalaries[$nat] ?? 0.0) + $salary;

            $sponsorTotals[$sponsor] = ($sponsorTotals[$sponsor] ?? 0) + 1;
            $sponsorSalaries[$sponsor] = ($sponsorSalaries[$sponsor] ?? 0.0) + $salary;
        }

        arsort($jobTotals);
        arsort($equipmentTypeTotals);
        arsort($branchTotals);
        arsort($nationalityTotals);
        arsort($sponsorTotals);

        $branches = array_keys($branchTotals);

        $jobRows = [];
        foreach ($jobTotals as $job => $count) {
            $counts = [];
            foreach ($branches as $br) {
                $counts[$br] = (int) ($jobBranchCounts[$job][$br] ?? 0);
            }
            $jobRows[] = [
                'job' => $job,
                'counts' => $counts,
                'total' => $count,
                'salary' => (float) ($jobSalaries[$job] ?? 0.0),
            ];
        }

        $equipmentTypeRows = [];
        foreach ($equipmentTypeTotals as $eqType => $count) {
            $counts = [];
            foreach ($branches as $br) {
                $counts[$br] = (int) ($equipmentTypeBranchCounts[$eqType][$br] ?? 0);
            }
            $equipmentTypeRows[] = [
                'type' => $eqType,
                'counts' => $counts,
                'total' => $count,
                'salary' => (float) ($equipmentTypeSalaries[$eqType] ?? 0.0),
            ];
        }

        $nationalityRows = [];
        foreach ($nationalityTotals as $nat => $count) {
            $nationalityRows[] = [
                'nationality' => $nat,
                'count' => $count,
                'percentage' => $grandTotal > 0 ? round(($count / $grandTotal) * 100, 1) : 0.0,
                'salary' => (float) ($nationalitySalaries[$nat] ?? 0.0),
            ];
        }

        $sponsorRows = [];
        foreach ($sponsorTotals as $sponsor => $count) {
            $sponsorRows[] = [
                'sponsor' => $sponsor,
                'count' => $count,
                'percentage' => $grandTotal > 0 ? round(($count / $grandTotal) * 100, 1) : 0.0,
                'salary' => (float) ($sponsorSalaries[$sponsor] ?? 0.0),
            ];
        }

        return [
            'grand_total' => $grandTotal,
            'total_salary' => $totalSalary,
            'jobs_count' => count($jobTotals),
            'nationalities_count' => count($nationalityTotals),
            'branches' => $branches,
            'branch_totals' => $branchTotals,
            'job_rows' => $jobRows,
            'equipment_type_rows' => $equipmentTypeRows,
            'nationality_rows' => $nationalityRows,
            'sponsor_rows' => $sponsorRows,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Driver
    {
        $distMap = $this->distributionLookupMap();
        $key = self::normalizeEquipmentCode((string) ($data['equipment_code'] ?? ''));
        if ($key !== '' && isset($distMap[$key])) {
            if (empty($data['branch']) && $distMap[$key]['branch'] !== '') {
                $data['branch'] = $distMap[$key]['branch'];
            }
            if (empty($data['site_location']) && $distMap[$key]['site_location'] !== '') {
                $data['site_location'] = $distMap[$key]['site_location'];
            }
        }

        $record = Driver::query()->create($data);
        DashboardService::clearCache();

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Driver $record, array $data): Driver
    {
        $record->update($data);
        DashboardService::clearCache();

        return $record->refresh();
    }

    public function delete(Driver $record): void
    {
        $record->delete();
        DashboardService::clearCache();
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(Driver $record, bool $includeAudit): array
    {
        return [
            'id' => $record->id,
            'staff_category' => $record->staff_category ?? Driver::CATEGORY_DRIVER,
            'equipment_code' => $record->equipment_code ?? '',
            'branch' => $record->branch ?? '',
            'site_location' => $record->site_location ?? '',
            'driver_name' => $record->driver_name ?? '',
            'employee_id' => $record->employee_id ?? '',
            'job_title' => $record->job_title ?? '',
            'nationality' => $record->nationality ?? '',
            'salary' => (string) ((float) $record->salary),
            'sponsor' => $record->sponsor ?? '',
            'status' => $record->status ?? '',
            'notes' => $record->notes ?? '',
            'audit_logs' => $includeAudit ? $this->auditLogPresenter->forModel($record) : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $isWorkshopStaff = ($filters['view'] ?? '') === 'workshop_staff'
            || ($filters['staff_category'] ?? '') === Driver::CATEGORY_WORKSHOP_STAFF;

        if ($isWorkshopStaff) {
            $headers = [
                'م',
                'الاسم',
                'الرقم الوظيفي',
                'الجنسية',
                'المهنة',
                'الراتب الإجمالي',
                'الفرع',
                'الحالة',
            ];

            $rows = [];
            $index = 1;
            foreach ($this->filteredQuery($filters)->cursor() as $record) {
                $rows[] = [
                    $index++,
                    $record->driver_name ?? '',
                    $record->employee_id ?? '',
                    $record->nationality ?? '',
                    $record->job_title ?? '',
                    (float) $record->salary,
                    $record->branch ?? '',
                    $record->status ?: 'نشط',
                ];
            }

            return $this->csvExportService->stream(
                'workshop-staff-'.now()->format('Y-m-d').'.xlsx',
                Driver::class,
                $headers,
                $rows,
                $filters,
            );
        }

        $headers = [
            'كود المعدة',
            'الفرع',
            'الموقع',
            'اسم السائق',
            'الرقم الوظيفي',
            'المهنة',
            'الجنسية',
            'الراتب',
            'الكفيل',
        ];

        $distMap = $this->distributionLookupMap();
        $rows = [];
        foreach ($this->filteredQuery($filters)->cursor() as $record) {
            $this->applyLiveDistribution($record, $distMap);
            $rows[] = [
                $record->equipment_code ?? '',
                $record->branch ?? '',
                $record->site_location ?? '',
                $record->driver_name ?? '',
                $record->employee_id ?? '',
                $record->job_title ?? '',
                $record->nationality ?? '',
                (float) $record->salary,
                $record->sponsor ?? '',
            ];
        }

        return $this->csvExportService->stream(
            'drivers-'.now()->format('Y-m-d').'.xlsx',
            Driver::class,
            $headers,
            $rows,
            $filters,
        );
    }
}
