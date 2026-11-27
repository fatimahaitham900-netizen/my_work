<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EquipmentBreakdown;
use App\Models\EquipmentDistribution;
use App\Models\IdleProjectEquipment;
use App\Models\RentalEquipment;
use App\Models\UnassignedDriver;
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
     * Normalize an Arabic equipment type label to a canonical root for smart matching
     * between singular rental types (e.g. حفار، قلاب، شيول) and plural distribution types (e.g. حفارات، قلابات، شيولات).
     */
    public static function normalizeEquipmentTypeRoot(?string $type): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', (string) $type) ?? '');
        if ($clean === '') {
            return '';
        }

        $clean = str_replace(['أ', 'إ', 'آ'], 'ا', $clean);
        $clean = str_replace(['ة', 'ى'], ['ه', 'ي'], $clean);
        $clean = mb_strtolower($clean, 'UTF-8');

        $synonyms = [
            'بوكلين' => 'حفار',
            'بوكلينات' => 'حفار',
            'حفارات' => 'حفار',
            'حفار' => 'حفار',
            'حفاره' => 'حفار',
            'قلابات' => 'قلاب',
            'قلاب' => 'قلاب',
            'شيولات' => 'شيول',
            'شيول' => 'شيول',
            'بلدوزرات' => 'بلدوزر',
            'بلدوزر' => 'بلدوزر',
            'دينات' => 'دينا',
            'دينا' => 'دينا',
            'رصاصات' => 'رصاصه',
            'رصاصه' => 'رصاصه',
            'مولدات كهرباء' => 'مولد',
            'مولدات' => 'مولد',
            'مولد كهرباء' => 'مولد',
            'مولد' => 'مولد',
            'تريلا صندوق' => 'تريلا',
            'تريلاصندوق' => 'تريلا',
            'تريلات' => 'تريلا',
            'تريلا' => 'تريلا',
            'فراده اسفلت' => 'فراده',
            'فراده اسفلتيه' => 'فراده',
            'فرادات' => 'فراده',
            'فراده' => 'فراده',
            'جريدر' => 'جريدر',
            'جريدرات' => 'جريدر',
            'كريدر' => 'جريدر',
            'كريدرات' => 'جريدر',
            'باك هو لودر' => 'باك هو',
            'باك هولودر' => 'باك هو',
            'باك هو' => 'باك هو',
            'بوبكات' => 'بوبكات',
            'بوب كات' => 'بوبكات',
            'كمبروسرات' => 'كمبروسر',
            'كمبروسر' => 'كمبروسر',
            'سطحات' => 'سطحه',
            'سطحه' => 'سطحه',
            'وايتات' => 'وايت',
            'وايت' => 'وايت',
            'رافعات' => 'رافعه',
            'رافعه' => 'رافعه',
            'كرينات' => 'كرين',
            'كرين' => 'كرين',
        ];

        if (isset($synonyms[$clean])) {
            return $synonyms[$clean];
        }

        // Strip Arabic plural suffix "ات" when length > 4
        if (mb_strlen($clean, 'UTF-8') > 4 && str_ends_with($clean, 'ات')) {
            $singular = mb_substr($clean, 0, -2, 'UTF-8');

            return $synonyms[$singular] ?? $singular;
        }

        return $clean;
    }

    /**
     * Check if a rental equipment type matches a distribution equipment type.
     */
    public static function equipmentTypesMatch(?string $rentalType, ?string $distType): bool
    {
        $rRoot = self::normalizeEquipmentTypeRoot($rentalType);
        $dRoot = self::normalizeEquipmentTypeRoot($distType);

        if ($rRoot === '' || $dRoot === '') {
            return false;
        }

        if ($rRoot === $dRoot) {
            return true;
        }

        return (mb_strlen($rRoot, 'UTF-8') >= 3 && str_contains($dRoot, $rRoot))
            || (mb_strlen($dRoot, 'UTF-8') >= 3 && str_contains($rRoot, $dRoot));
    }

    /**
     * Analyze rented equipment against non-operating company-owned equipment in توزيعة المعدات
     * (متوقفة بدون عمل، متوقفة بدون سائق، متعطلة في الورشة، متوقفة لأسباب أخرى).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function replacementAnalysis(array $filters = []): array
    {
        $rentals = $this->filteredQuery($filters)->get();
        $distributions = EquipmentDistribution::query()
            ->get(['id', 'equipment_code', 'equipment_type', 'branch', 'site_location', 'equipment_status', 'notes']);
        $idleProjectEquipments = IdleProjectEquipment::query()
            ->get(['id', 'equipment_code', 'equipment_type', 'branch', 'site_location', 'equipment_status', 'total_idle_days']);
        $unassignedDrivers = UnassignedDriver::query()
            ->get(['id', 'driver_name', 'employee_id', 'job_title', 'location', 'notes']);

        // Single-query lookup map from normalized equipment_code => EquipmentBreakdown (ZERO N+1!)
        $breakdownsByCode = [];
        foreach (EquipmentBreakdown::query()->get(['equipment_code', 'fault_description', 'breakdown_date', 'workshop_status', 'repair_status']) as $bd) {
            $norm = DriverService::normalizeEquipmentCode($bd->equipment_code);
            if ($norm !== '') {
                $breakdownsByCode[$norm] = $bd;
            }
        }

        // Group rentals by normalized equipment_type label
        $rentalsByType = [];
        foreach ($rentals as $rental) {
            $label = trim((string) ($rental->equipment_type ?? '')) ?: 'غير محدد';
            if (! isset($rentalsByType[$label])) {
                $rentalsByType[$label] = [
                    'type' => $label,
                    'rented_count' => 0,
                    'rented_amount' => 0.0,
                    'branches' => [],
                ];
            }
            $rentalsByType[$label]['rented_count']++;
            $rentalsByType[$label]['rented_amount'] += (float) $rental->rental_amount;
            $br = trim((string) ($rental->branch ?? ''));
            if ($br !== '' && ! in_array($br, $rentalsByType[$label]['branches'], true)) {
                $rentalsByType[$label]['branches'][] = $br;
            }
        }

        $rows = [];
        $totalRentedCount = 0;
        $totalRentedAmount = 0.0;
        $totalIdleNoWork = 0;
        $totalNoDriver = 0;
        $totalBrokenWorkshop = 0;
        $totalOtherStopped = 0;
        $totalReplaceableUnits = 0;
        $totalPotentialSavings = 0.0;

        foreach ($rentalsByType as $typeInfo) {
            $typeLabel = $typeInfo['type'];
            $rentedCount = $typeInfo['rented_count'];
            $rentedAmount = $typeInfo['rented_amount'];
            $rentedBranches = $typeInfo['branches'];

            $idleNoWork = 0;
            $noDriver = 0;
            $brokenWorkshop = 0;
            $otherStopped = 0;
            $sameBranchCount = 0;
            $matchedDistTypes = [];
            $candidates = [];
            $seenCodes = [];

            foreach ($distributions as $dist) {
                if (! self::equipmentTypesMatch($typeLabel, $dist->equipment_type)) {
                    continue;
                }

                $status = trim((string) ($dist->equipment_status ?? ''));
                $notes = trim((string) ($dist->notes ?? ''));
                $noteHasNoDriver = str_contains($notes, 'بدون سائق');
                $noteHasNoWork = str_contains($notes, 'بدون عمل');

                // Skip actively operating equipment UNLESS its notes explicitly flag بدون عمل or بدون سائق
                if ((str_contains($status, 'تشغيل') || $status === '') && ! $noteHasNoDriver && ! $noteHasNoWork) {
                    continue;
                }

                $category = match (true) {
                    str_contains($status, 'بدون سائق') || $noteHasNoDriver => 'no_driver',
                    str_contains($status, 'متعطلة') || str_contains($status, 'ورشة') || str_contains($status, 'عطل') => 'broken_workshop',
                    str_contains($status, 'بدون عمل') || $noteHasNoWork => 'idle_no_work',
                    default => 'other_stopped',
                };

                if ($category === 'idle_no_work') {
                    $idleNoWork++;
                } elseif ($category === 'no_driver') {
                    $noDriver++;
                } elseif ($category === 'broken_workshop') {
                    $brokenWorkshop++;
                } else {
                    $otherStopped++;
                }

                $distBranch = trim((string) ($dist->branch ?? ''));
                $isSameBranch = $distBranch !== '' && in_array($distBranch, $rentedBranches, true);
                if ($isSameBranch) {
                    $sameBranchCount++;
                }

                $distTypeClean = trim((string) ($dist->equipment_type ?? ''));
                if ($distTypeClean !== '' && ! in_array($distTypeClean, $matchedDistTypes, true)) {
                    $matchedDistTypes[] = $distTypeClean;
                }

                $normCode = DriverService::normalizeEquipmentCode($dist->equipment_code);
                if ($normCode !== '') {
                    $seenCodes[$normCode] = true;
                }

                $displayStatus = $status;
                if (str_contains($status, 'تشغيل')) {
                    $displayStatus = $noteHasNoDriver ? 'متوقفة بدون سائق' : 'بدون عمل فعلي';
                }

                $matchedBd = ($category === 'broken_workshop' && $normCode !== '')
                    ? ($breakdownsByCode[$normCode] ?? null)
                    : null;

                $candidates[] = [
                    'equipment_code' => $dist->equipment_code ?: '—',
                    'equipment_type' => $distTypeClean ?: '—',
                    'branch' => $distBranch ?: '—',
                    'site_location' => trim((string) ($dist->site_location ?? '')) ?: '—',
                    'equipment_status' => $displayStatus,
                    'category' => $category,
                    'fault_description' => trim((string) ($matchedBd?->fault_description ?? '')),
                    'breakdown_date' => $matchedBd?->breakdown_date?->format('Y-m-d') ?? '',
                    'downtime_days' => $matchedBd !== null ? (int) $matchedBd->downtime_days : null,
                    'workshop_status' => trim((string) ($matchedBd?->workshop_status ?? '')),
                    'notes' => $notes,
                    'same_branch' => $isSameBranch,
                ];
            }

            // Also include matching equipment from معدات بدون عمل فعلي بالمشاريع (deduplicated by equipment_code)
            foreach ($idleProjectEquipments as $idleEq) {
                if (! self::equipmentTypesMatch($typeLabel, $idleEq->equipment_type)) {
                    continue;
                }

                $normCode = DriverService::normalizeEquipmentCode($idleEq->equipment_code);
                if ($normCode !== '' && isset($seenCodes[$normCode])) {
                    continue;
                }
                if ($normCode !== '') {
                    $seenCodes[$normCode] = true;
                }

                $idleNoWork++;
                $idleBranch = trim((string) ($idleEq->branch ?? ''));
                $isSameBranch = $idleBranch !== '' && in_array($idleBranch, $rentedBranches, true);
                if ($isSameBranch) {
                    $sameBranchCount++;
                }

                $daysNote = (int) $idleEq->total_idle_days > 0
                    ? 'بدون عمل فعلي منذ '.$idleEq->total_idle_days.' يوم (معدات بدون عمل بالمشاريع)'
                    : 'معدات بدون عمل بالمشاريع';

                $candidates[] = [
                    'equipment_code' => $idleEq->equipment_code ?: '—',
                    'equipment_type' => trim((string) ($idleEq->equipment_type ?? '')) ?: '—',
                    'branch' => $idleBranch ?: '—',
                    'site_location' => trim((string) ($idleEq->site_location ?? '')) ?: '—',
                    'equipment_status' => trim((string) ($idleEq->equipment_status ?? '')) ?: 'بدون عمل فعلي',
                    'category' => 'idle_no_work',
                    'fault_description' => '',
                    'breakdown_date' => '',
                    'downtime_days' => null,
                    'workshop_status' => '',
                    'notes' => $daysNote,
                    'same_branch' => $isSameBranch,
                ];
            }

            // Sort candidates by priority: same branch first, then idle_no_work -> no_driver -> broken_workshop -> other
            $priorityMap = ['idle_no_work' => 1, 'no_driver' => 2, 'broken_workshop' => 3, 'other_stopped' => 4];
            usort($candidates, static function (array $a, array $b) use ($priorityMap): int {
                if ($a['same_branch'] !== $b['same_branch']) {
                    return $a['same_branch'] ? -1 : 1;
                }

                return ($priorityMap[$a['category']] ?? 9) <=> ($priorityMap[$b['category']] ?? 9);
            });

            $totalAlternatives = $idleNoWork + $noDriver + $brokenWorkshop + $otherStopped;
            $replaceableCount = min($rentedCount, $totalAlternatives);
            $potentialSavings = $rentedCount > 0 ? round(($replaceableCount / $rentedCount) * $rentedAmount, 0) : 0.0;

            [$recommendation, $recommendationTone] = match (true) {
                $idleNoWork > 0 => ['يمكن الإحلال فوراً (معدات متوقفة بدون عمل)', 'emerald'],
                $noDriver > 0 => ['يمكن التشغيل بتوجيه سائق بديل الإيجار', 'amber'],
                $brokenWorkshop > 0 => ['إصلاح عاجل بالورشة بديل الإيجار', 'rose'],
                $otherStopped > 0 => ['مراجعة أسباب التوقف لتشغيل البديل', 'sky'],
                default => ['لا يوجد بديل متوقف حالياً في التوزيعة', 'slate'],
            };

            $matchingDrivers = [];
            foreach ($unassignedDrivers as $uDriver) {
                $jobClean = trim(str_replace(['سائق', 'مشغل'], '', (string) ($uDriver->job_title ?? '')));
                if ($jobClean !== '' && self::equipmentTypesMatch($typeLabel, $jobClean)) {
                    $matchingDrivers[] = [
                        'driver_name' => $uDriver->driver_name ?: '—',
                        'employee_id' => $uDriver->employee_id ?: '—',
                        'job_title' => $uDriver->job_title ?: '—',
                        'location' => $uDriver->location ?: '—',
                        'notes' => trim((string) ($uDriver->notes ?? '')),
                    ];
                }
            }

            $rows[] = [
                'type' => $typeLabel,
                'matched_dist_types' => $matchedDistTypes,
                'rented_count' => $rentedCount,
                'rented_amount' => $rentedAmount,
                'rented_branches' => $rentedBranches,
                'idle_no_work' => $idleNoWork,
                'no_driver' => $noDriver,
                'broken_workshop' => $brokenWorkshop,
                'other_stopped' => $otherStopped,
                'total_alternatives' => $totalAlternatives,
                'same_branch_count' => $sameBranchCount,
                'replaceable_count' => $replaceableCount,
                'potential_savings' => $potentialSavings,
                'recommendation' => $recommendation,
                'recommendation_tone' => $recommendationTone,
                'candidates' => $candidates,
                'matching_drivers' => $matchingDrivers,
            ];

            $totalRentedCount += $rentedCount;
            $totalRentedAmount += $rentedAmount;
            $totalIdleNoWork += $idleNoWork;
            $totalNoDriver += $noDriver;
            $totalBrokenWorkshop += $brokenWorkshop;
            $totalOtherStopped += $otherStopped;
            $totalReplaceableUnits += $replaceableCount;
            $totalPotentialSavings += $potentialSavings;
        }

        // Sort rows by highest potential savings then highest rented amount
        usort($rows, static fn (array $a, array $b): int => [$b['potential_savings'], $b['rented_amount']] <=> [$a['potential_savings'], $a['rented_amount']]);

        return [
            'rows' => $rows,
            'totals' => [
                'rented_count' => $totalRentedCount,
                'rented_amount' => $totalRentedAmount,
                'idle_no_work' => $totalIdleNoWork,
                'no_driver' => $totalNoDriver,
                'broken_workshop' => $totalBrokenWorkshop,
                'other_stopped' => $totalOtherStopped,
                'total_alternatives' => $totalIdleNoWork + $totalNoDriver + $totalBrokenWorkshop + $totalOtherStopped,
                'replaceable_units' => $totalReplaceableUnits,
                'potential_savings' => $totalPotentialSavings,
            ],
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
