<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BreakdownCategory;
use App\Enums\BreakdownSeverity;
use App\Enums\ComplianceStatus;
use App\Enums\OwnershipTransferStatus;
use App\Enums\UnassignedDriverStatus;
use App\Models\BreakdownDriverStoppage;
use App\Models\CarBreakdown;
use App\Models\DashboardMetric;
use App\Models\EquipmentBreakdown;
use App\Models\FinancialSupportTransfer;
use App\Models\IdleProjectEquipment;
use App\Models\OwnershipTransfer;
use App\Models\RegulatoryDocument;
use App\Models\SparePartTransfer;
use App\Models\TransportViolation;
use App\Models\UnassignedDriver;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public const string CACHE_KEY = 'executive_dashboard.summary.v18';

    public const int CACHE_TTL_SECONDS = 300;

    /**
     * Retrieve cached executive KPI summary, chart series, and critical feed.
     *
     * @return array<string, mixed>
     */
    public function getSummary(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            self::clearCache();
        }

        /** @var array<string, mixed> $summary */
        $summary = Cache::remember(
            self::CACHE_KEY,
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            fn (): array => $this->buildSummary(),
        );

        return $summary;
    }

    /**
     * Retrieve compact counts for master layout navigation and quick-alert counter.
     *
     * @return array{
     *     total_active_deviations: int,
     *     total_critical_alerts: int,
     *     module_counts: array<string, array{active: int, critical: int, title: string, route: string, route_params: array<string, string>}>
     * }
     */
    public function getNavigationAlertSummary(): array
    {
        $summary = $this->getSummary();
        $moduleCounts = [];

        foreach ($summary['modules'] as $module) {
            $moduleCounts[$module['key']] = [
                'active' => $module['active_count'],
                'critical' => $module['critical_count'],
                'title' => $module['title'],
                'route' => $module['route'],
                'route_params' => $module['route_params'],
            ];
        }

        foreach (['transport_violations', 'spare_part_transfers', 'car_breakdowns', 'idle_project_equipment', 'financial_support', 'maintenance_deviations'] as $extraKey) {
            if (isset($summary['navigation_extras'][$extraKey])) {
                $moduleCounts[$extraKey] = $summary['navigation_extras'][$extraKey];
            }
        }

        return [
            'total_active_deviations' => $summary['kpis']['total_active_deviations'],
            'total_critical_alerts' => $summary['kpis']['total_critical_alerts'],
            'module_counts' => $moduleCounts,
        ];
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSummary(): array
    {
        $today = today();
        $stoppageCriticalCutoff = $today->copy()->subDays(BreakdownDriverStoppage::CRITICAL_IDLE_DAYS_THRESHOLD);
        $unassignedCriticalCutoff = $today->copy()->subDays(UnassignedDriver::CRITICAL_IDLE_DAYS_THRESHOLD);
        $stoppageMediumCutoff = $today->copy()->subDays(3);

        // 1. Ownership Transfers
        $transfersTotal = OwnershipTransfer::query()->count();
        $transfersActive = OwnershipTransfer::query()->active()->count();
        $transfersCritical = OwnershipTransfer::query()->critical()->count();
        $transfersNonCritical = max(0, $transfersActive - $transfersCritical);

        // 2. Breakdown Driver Stoppages
        $stoppagesTotal = BreakdownDriverStoppage::query()->count();
        $stoppagesActive = BreakdownDriverStoppage::query()->active()->count();
        $stoppagesCritical = BreakdownDriverStoppage::query()->critical()->count();
        $stoppagesMedium = BreakdownDriverStoppage::query()
            ->active()
            ->whereDate('stoppage_start_date', '>', $stoppageCriticalCutoff)
            ->whereDate('stoppage_start_date', '<=', $stoppageMediumCutoff)
            ->count();
        $stoppagesMinor = max(0, $stoppagesActive - $stoppagesCritical - $stoppagesMedium);

        $totalIdleDriverDays = (int) BreakdownDriverStoppage::query()
            ->active()
            ->get(['stoppage_start_date'])
            ->sum(static fn (BreakdownDriverStoppage $stoppage): int => $stoppage->idle_days);

        // 3. Unassigned Drivers
        $unassignedTotal = UnassignedDriver::query()->count();
        $unassignedActive = UnassignedDriver::query()->active()->count();
        $unassignedCritical = UnassignedDriver::query()->critical()->count();
        $unassignedMedium = UnassignedDriver::query()
            ->where('status', UnassignedDriverStatus::Ready->value)
            ->whereDate('unassigned_since', '>', $unassignedCriticalCutoff)
            ->whereDate('license_expiry', '>=', $today)
            ->count();
        $unassignedMinor = max(0, $unassignedActive - $unassignedCritical - $unassignedMedium);

        // 4. Equipment Breakdowns
        $breakdownsTotal = EquipmentBreakdown::query()->count();
        $breakdownsActive = EquipmentBreakdown::query()->active()->count();
        $breakdownsCritical = EquipmentBreakdown::query()->critical()->count();
        $breakdownsMedium = EquipmentBreakdown::query()
            ->active()
            ->where('severity', BreakdownSeverity::Medium->value)
            ->count();
        $breakdownsMinor = EquipmentBreakdown::query()
            ->active()
            ->where('severity', BreakdownSeverity::Minor->value)
            ->count();
        $sparePartsDelays = EquipmentBreakdown::query()->waitingParts()->count();
        $activeBreakdownCost = (float) EquipmentBreakdown::query()->active()->sum('estimated_cost');

        // 5. Regulatory Documents
        $documentsTotal = RegulatoryDocument::query()->count();
        $documentsActive = RegulatoryDocument::query()->deviations()->count();
        $documentsCritical = RegulatoryDocument::query()->critical()->count();
        $documentsExpiringSoon = RegulatoryDocument::query()->expiringSoon()->count();
        $expiredPeriodicInspections = RegulatoryDocument::query()->expiredPeriodicInspections()->count();
        $expiredOperatingCards = RegulatoryDocument::query()->expiredOperatingCards()->count();
        $expiredRegistrations = RegulatoryDocument::query()->expiredRegistrations()->count();
        $regulatoryRenewalCost = (float) RegulatoryDocument::query()->deviations()->sum('renewal_cost');

        // 6. Transport Violations
        $violationsTotal = TransportViolation::query()->count();
        $violationsAmount = (float) TransportViolation::query()->sum('amount');
        $violationsPending = TransportViolation::query()->pendingObjection()->count();

        // 7. Spare Part Transfers
        $sparePartTransfersTotal = SparePartTransfer::query()->count();
        $sparePartTransfersUnknown = SparePartTransfer::query()->unknownSource()->count();

        // 8. Car Breakdowns
        $carBreakdownsTotal = CarBreakdown::query()->count();
        $carBreakdownsMajor = CarBreakdown::query()->majorMechanical()->count();

        // 9. Idle Project Equipment
        $idleProjectEquipmentTotal = IdleProjectEquipment::query()->count();
        $idleProjectEquipmentHigh = IdleProjectEquipment::query()->highIdleDays(7)->count();

        // 10. Financial Support
        $financialSupportTotal = FinancialSupportTransfer::query()->count();
        $financialSupportUnderRepair = FinancialSupportTransfer::query()->underRepair()->count();
        $financialSupportSummary = app(FinancialSupportService::class)->dashboardTableSummary();

        // 11. Custom Editable Executive Dashboard Metrics
        $registrationAmendmentRaw = DashboardMetric::getValue(DashboardMetric::KEY_REGISTRATION_AMENDMENT, '0');
        $registrationAmendmentDisplay = is_numeric($registrationAmendmentRaw)
            ? number_format((float) $registrationAmendmentRaw)
            : $registrationAmendmentRaw;
        $dieselControlRaw = DashboardMetric::getValue(DashboardMetric::KEY_DIESEL_CONTROL, 'لا يوجد');
        $carsExpiredInspectionRaw = DashboardMetric::getValue(DashboardMetric::KEY_CARS_EXPIRED_INSPECTION, '0');
        $carsExpiredInspectionDisplay = is_numeric($carsExpiredInspectionRaw)
            ? number_format((float) $carsExpiredInspectionRaw)
            : $carsExpiredInspectionRaw;

        $highOilConsumptionRaw = DashboardMetric::getValue(DashboardMetric::KEY_HIGH_OIL_CONSUMPTION, '0');
        $highOilConsumptionDisplay = is_numeric($highOilConsumptionRaw)
            ? number_format((float) $highOilConsumptionRaw)
            : $highOilConsumptionRaw;

        $totalRentalEquipmentRaw = DashboardMetric::getValue(DashboardMetric::KEY_TOTAL_RENTAL_EQUIPMENT, '0');
        $totalRentalEquipmentDisplay = is_numeric($totalRentalEquipmentRaw)
            ? number_format((float) $totalRentalEquipmentRaw)
            : $totalRentalEquipmentRaw;

        $equipmentInvestigationsRaw = DashboardMetric::getValue(DashboardMetric::KEY_EQUIPMENT_INVESTIGATIONS, '0');
        $equipmentInvestigationsDisplay = is_numeric($equipmentInvestigationsRaw)
            ? number_format((float) $equipmentInvestigationsRaw)
            : $equipmentInvestigationsRaw;

        $monthlyCertificateDelaysRaw = DashboardMetric::getValue(DashboardMetric::KEY_MONTHLY_CERTIFICATE_DELAYS, '0');
        $monthlyCertificateDelaysDisplay = is_numeric($monthlyCertificateDelaysRaw)
            ? number_format((float) $monthlyCertificateDelaysRaw)
            : $monthlyCertificateDelaysRaw;

        $month12TargetsRaw = DashboardMetric::getValue(DashboardMetric::KEY_MONTH_12_TARGETS, '0');
        $month12TargetsDisplay = is_numeric($month12TargetsRaw)
            ? number_format((float) $month12TargetsRaw)
            : $month12TargetsRaw;

        $departmentPlanRaw = DashboardMetric::getValue(DashboardMetric::KEY_DEPARTMENT_PLAN, '0');
        $departmentPlanDisplay = is_numeric($departmentPlanRaw)
            ? number_format((float) $departmentPlanRaw)
            : $departmentPlanRaw;

        $dieselFundingProjectsRaw = DashboardMetric::getValue(DashboardMetric::KEY_DIESEL_FUNDING_PROJECTS, '0');
        $dieselFundingProjectsDisplay = is_numeric($dieselFundingProjectsRaw)
            ? number_format((float) $dieselFundingProjectsRaw)
            : $dieselFundingProjectsRaw;

        // 12. Maintenance Deviations
        $maintenanceSummary = app(MaintenanceDeviationService::class)->summaryAverages();

        $totalActiveDeviations = $transfersActive
            + $stoppagesActive
            + $unassignedActive
            + $breakdownsActive
            + $documentsActive;

        $totalCriticalAlerts = $transfersCritical
            + $stoppagesCritical
            + $unassignedCritical
            + $breakdownsCritical
            + $documentsCritical;

        $totalMediumAlerts = $transfersNonCritical
            + $stoppagesMedium
            + $unassignedMedium
            + $breakdownsMedium
            + $documentsExpiringSoon;

        $totalMinorAlerts = $stoppagesMinor + $unassignedMinor + $breakdownsMinor;

        $modules = [
            $this->makeModuleCard(
                key: 'ownership_transfers',
                title: 'نقل ملكيات المعدات',
                shortTitle: 'نقل الملكيات',
                subtitle: 'طلبات النقل المعلقة والمتأخرة عن الموعد',
                route: 'ownership-transfers.index',
                routeParams: $transfersCritical > 0 ? ['status' => OwnershipTransferStatus::Delayed->value] : ($transfersActive > 0 ? ['focus' => 'active'] : []),
                activeCount: $transfersActive,
                criticalCount: $transfersCritical,
                totalCount: $transfersTotal,
                criticalLabel: 'متأخرة عن الموعد',
                accent: 'indigo',
            ),
            $this->makeModuleCard(
                key: 'breakdown_driver_stoppages',
                title: 'سائقين متوقفين على أعطال',
                shortTitle: 'سائقين متوقفين',
                subtitle: 'سائقون بانتظار إصلاح معداتهم المعطلة',
                route: 'breakdown-stoppages.index',
                routeParams: $stoppagesCritical > 0 ? ['focus' => 'critical'] : ($stoppagesActive > 0 ? ['status' => 'waiting'] : []),
                activeCount: $stoppagesActive,
                criticalCount: $stoppagesCritical,
                totalCount: $stoppagesTotal,
                criticalLabel: 'توقف 7 أيام فأكثر',
                accent: 'rose',
            ),
            $this->makeModuleCard(
                key: 'unassigned_drivers',
                title: 'سائقين بدون معدات',
                shortTitle: 'بدون معدات',
                subtitle: 'سائقون جاهزون أو تحت التقييم بلا تكليف',
                route: 'unassigned-drivers.index',
                routeParams: $unassignedCritical > 0 ? ['focus' => 'critical'] : ($unassignedActive > 0 ? ['status' => UnassignedDriverStatus::Ready->value] : []),
                activeCount: $unassignedActive,
                criticalCount: $unassignedCritical,
                totalCount: $unassignedTotal,
                criticalLabel: 'خمول +14 يوماً أو رخصة منتهية',
                accent: 'amber',
            ),
            $this->makeModuleCard(
                key: 'equipment_breakdowns',
                title: 'معدات متعطلة بالورش',
                shortTitle: 'معدات متعطلة بالورش',
                subtitle: 'معدات قيد الإصلاح أو بانتظار قطع الغيار',
                route: 'equipment-breakdowns.index',
                routeParams: $breakdownsCritical > 0 ? ['severity' => BreakdownSeverity::Critical->value, 'focus' => 'active'] : ($breakdownsActive > 0 ? ['focus' => 'active'] : []),
                activeCount: $breakdownsActive,
                criticalCount: $breakdownsCritical,
                totalCount: $breakdownsTotal,
                criticalLabel: 'أعطال ذات خطورة حرجة',
                accent: 'sky',
            ),
            $this->makeModuleCard(
                key: 'regulatory_documents',
                title: 'الأوراق النظامية',
                shortTitle: 'الأوراق النظامية',
                subtitle: 'وثائق منتهية أو مفقودة أو قاربت على الانتهاء',
                route: 'regulatory-documents.index',
                routeParams: $documentsCritical > 0 ? ['focus' => 'critical'] : ($documentsActive > 0 ? ['compliance_status' => ComplianceStatus::ExpiringSoon->value] : []),
                activeCount: $documentsActive,
                criticalCount: $documentsCritical,
                totalCount: $documentsTotal,
                criticalLabel: 'منتهية أو مفقودة',
                accent: 'emerald',
            ),
        ];

        $executiveCards = [
            [
                'key' => 'breakdown_driver_stoppages',
                'title' => 'سائقين متوقفين على أعطال المعدات',
                'route' => 'breakdown-stoppages.index',
                'span' => 'lg:col-span-3',
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => number_format($stoppagesTotal), 'color' => 'text-purple-700', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'unassigned_drivers',
                'title' => 'سائقين بدون معدات',
                'route' => 'unassigned-drivers.index',
                'span' => 'lg:col-span-3',
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => number_format($unassignedTotal), 'color' => 'text-amber-600', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'equipment_breakdowns',
                'title' => 'معدات متعطلة بالورش',
                'route' => 'equipment-breakdowns.index',
                'span' => 'lg:col-span-3',
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => number_format($breakdownsTotal), 'color' => 'text-rose-600', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'car_breakdowns',
                'title' => 'السيارات المتعطلة بالورش',
                'route' => 'car-breakdowns.index',
                'span' => 'lg:col-span-3',
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => number_format($carBreakdownsTotal), 'color' => 'text-blue-600', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'expired_periodic_inspections',
                'title' => 'معدات منتهية الفحص الدوري',
                'route' => 'regulatory-documents.index',
                'route_params' => [
                    'document_type' => 'فحص دوري',
                    'compliance_status' => ComplianceStatus::Expired->value,
                ],
                'span' => 'lg:col-span-3',
                'cells' => [
                    [
                        'label' => 'إجمالي العدد',
                        'value' => number_format($expiredPeriodicInspections),
                        'color' => 'text-rose-600',
                        'tinted' => false,
                        'params' => [
                            'document_type' => 'فحص دوري',
                            'compliance_status' => ComplianceStatus::Expired->value,
                        ],
                    ],
                ],
            ],
            [
                'key' => 'expired_operating_cards',
                'title' => 'كروت تشغيل منتهية معدات',
                'route' => 'regulatory-documents.index',
                'route_params' => [
                    'document_type' => 'كرت تشغيل',
                    'compliance_status' => ComplianceStatus::Expired->value,
                ],
                'span' => 'lg:col-span-3',
                'cells' => [
                    [
                        'label' => 'إجمالي العدد',
                        'value' => number_format($expiredOperatingCards),
                        'color' => 'text-amber-600',
                        'tinted' => false,
                        'params' => [
                            'document_type' => 'كرت تشغيل',
                            'compliance_status' => ComplianceStatus::Expired->value,
                        ],
                    ],
                ],
            ],
            [
                'key' => 'expired_registrations',
                'title' => 'استمارات منتهية معدات',
                'route' => 'regulatory-documents.index',
                'route_params' => [
                    'document_type' => 'استمارة',
                    'compliance_status' => ComplianceStatus::Expired->value,
                ],
                'span' => 'lg:col-span-3',
                'cells' => [
                    [
                        'label' => 'إجمالي العدد',
                        'value' => number_format($expiredRegistrations),
                        'color' => 'text-purple-700',
                        'tinted' => false,
                        'params' => [
                            'document_type' => 'استمارة',
                            'compliance_status' => ComplianceStatus::Expired->value,
                        ],
                    ],
                ],
            ],
            [
                'key' => 'cars_expired_periodic_inspection',
                'title' => 'سيارات منتهي فحصها الدوري',
                'route' => 'car-breakdowns.index',
                'span' => 'lg:col-span-3',
                'editable' => true,
                'metric_key' => DashboardMetric::KEY_CARS_EXPIRED_INSPECTION,
                'raw_value' => $carsExpiredInspectionRaw,
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => $carsExpiredInspectionDisplay, 'color' => 'text-rose-600', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'spare_part_transfers',
                'title' => 'نقل قطع غيار بدون تعميد الإدارة',
                'route' => 'spare-part-transfers.index',
                'span' => 'lg:col-span-3',
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => number_format($sparePartTransfersTotal), 'color' => 'text-slate-950', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'ownership_transfers',
                'title' => 'معدات تحتاج إلى نقل ملكية',
                'route' => 'ownership-transfers.index',
                'span' => 'lg:col-span-3',
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => number_format($transfersTotal), 'color' => 'text-blue-600', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'idle_project_equipment',
                'title' => 'معدات بدون عمل فعلي بالمشاريع',
                'route' => 'idle-project-equipment.index',
                'span' => 'lg:col-span-3',
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => number_format($idleProjectEquipmentTotal), 'color' => 'text-purple-700', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'registration_amendment_needed',
                'title' => 'معدات تحتاج إلى تعديل استمارة',
                'route' => 'regulatory-documents.index',
                'route_params' => ['document_type' => 'استمارة'],
                'span' => 'lg:col-span-3',
                'editable' => true,
                'metric_key' => DashboardMetric::KEY_REGISTRATION_AMENDMENT,
                'raw_value' => $registrationAmendmentRaw,
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => $registrationAmendmentDisplay, 'color' => 'text-amber-600', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'diesel_consumption_control',
                'title' => 'معدلات استهلاك الديزل والرقابة على الصرف',
                'route' => 'dashboard',
                'span' => 'lg:col-span-3',
                'editable' => true,
                'metric_key' => DashboardMetric::KEY_DIESEL_CONTROL,
                'raw_value' => $dieselControlRaw,
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => $dieselControlRaw, 'color' => 'text-emerald-600', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'high_oil_consumption_equipment',
                'title' => 'تزويد زيوت للمعدات الذي صرفها عالي',
                'route' => 'dashboard',
                'span' => 'lg:col-span-3',
                'editable' => true,
                'metric_key' => DashboardMetric::KEY_HIGH_OIL_CONSUMPTION,
                'raw_value' => $highOilConsumptionRaw,
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => $highOilConsumptionDisplay, 'color' => 'text-amber-600', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'total_project_rental_equipment',
                'title' => 'إجمالي معدات الإيجار بالمشاريع',
                'route' => 'dashboard',
                'span' => 'lg:col-span-3',
                'editable' => true,
                'metric_key' => DashboardMetric::KEY_TOTAL_RENTAL_EQUIPMENT,
                'raw_value' => $totalRentalEquipmentRaw,
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => $totalRentalEquipmentDisplay, 'color' => 'text-blue-600', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'equipment_department_investigations',
                'title' => 'تحقيقات إدارة المعدات',
                'route' => 'dashboard',
                'span' => 'lg:col-span-3',
                'editable' => true,
                'metric_key' => DashboardMetric::KEY_EQUIPMENT_INVESTIGATIONS,
                'raw_value' => $equipmentInvestigationsRaw,
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => $equipmentInvestigationsDisplay, 'color' => 'text-purple-700', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'monthly_payment_certificate_delays',
                'title' => 'التأخر في المستخلصات الشهرية',
                'route' => 'dashboard',
                'span' => 'lg:col-span-3',
                'editable' => true,
                'metric_key' => DashboardMetric::KEY_MONTHLY_CERTIFICATE_DELAYS,
                'raw_value' => $monthlyCertificateDelaysRaw,
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => $monthlyCertificateDelaysDisplay, 'color' => 'text-rose-600', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'project_targets_month_12',
                'title' => 'مستهدفات المشاريع شهر 12',
                'route' => 'dashboard',
                'span' => 'lg:col-span-3',
                'editable' => true,
                'metric_key' => DashboardMetric::KEY_MONTH_12_TARGETS,
                'raw_value' => $month12TargetsRaw,
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => $month12TargetsDisplay, 'color' => 'text-emerald-600', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'equipment_department_plan',
                'title' => 'خطة إدارة المعدات',
                'route' => 'dashboard',
                'span' => 'lg:col-span-3',
                'editable' => true,
                'metric_key' => DashboardMetric::KEY_DEPARTMENT_PLAN,
                'raw_value' => $departmentPlanRaw,
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => $departmentPlanDisplay, 'color' => 'text-slate-950', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'projects_needing_diesel_funding',
                'title' => 'المشاريع التي تحتاج لها تمويل ديزل',
                'route' => 'dashboard',
                'span' => 'lg:col-span-3',
                'editable' => true,
                'metric_key' => DashboardMetric::KEY_DIESEL_FUNDING_PROJECTS,
                'raw_value' => $dieselFundingProjectsRaw,
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => $dieselFundingProjectsDisplay, 'color' => 'text-blue-600', 'tinted' => false, 'params' => []],
                ],
            ],
            [
                'key' => 'transport_violations',
                'title' => 'مخالفات وزارة النقل',
                'route' => 'transport-violations.index',
                'span' => 'sm:col-span-2 lg:col-span-6',
                'wide' => true,
                'cells' => [
                    ['label' => 'إجمالي العدد', 'value' => number_format($violationsTotal), 'color' => 'text-rose-600', 'tinted' => false, 'params' => []],
                    ['label' => 'إجمالي المبالغ (ر.س)', 'value' => number_format($violationsAmount, 0), 'color' => 'text-emerald-600', 'tinted' => true, 'params' => []],
                ],
            ],
            [
                'key' => 'maintenance_deviations',
                'title' => 'انحراف الصيانات',
                'route' => 'maintenance-deviations.index',
                'span' => 'sm:col-span-2 lg:col-span-6',
                'wide' => true,
                'cells' => [
                    ['label' => 'نسبة انحراف الصيانة العادية', 'value' => $maintenanceSummary['daily_deviation_rate'].'%', 'color' => 'text-blue-600', 'tinted' => false, 'params' => []],
                    ['label' => 'نسبة انحراف الصيانة الوقائية', 'value' => $maintenanceSummary['preventive_deviation_rate'].'%', 'color' => 'text-rose-600', 'tinted' => true, 'params' => []],
                ],
            ],
        ];

        return [
            'generated_at' => now()->format('Y-m-d H:i'),
            'kpis' => [
                'total_active_deviations' => $totalActiveDeviations,
                'total_critical_alerts' => $totalCriticalAlerts,
                'estimated_financial_exposure' => round($activeBreakdownCost + $regulatoryRenewalCost, 2),
                'spare_parts_delays' => $sparePartsDelays,
                'total_idle_driver_days' => $totalIdleDriverDays,
            ],
            'modules' => $modules,
            'executive_cards' => $executiveCards,
            'financial_support_summary' => $financialSupportSummary,
            'charts' => [
                'by_module' => [
                    'labels' => array_column($modules, 'short_title'),
                    'active' => array_column($modules, 'active_count'),
                    'critical' => array_column($modules, 'critical_count'),
                ],
                'breakdowns_by_category' => $this->buildBreakdownsByCategoryChart(),
                'by_severity' => [
                    'labels' => ['حرج', 'متوسط', 'بسيط'],
                    'counts' => [$totalCriticalAlerts, $totalMediumAlerts, $totalMinorAlerts],
                    'colors' => ['#e11d48', '#f59e0b', '#0284c7'],
                ],
            ],
            'critical_feed' => $this->buildCriticalFeed(),
            'navigation_extras' => [
                'transport_violations' => [
                    'active' => $violationsTotal,
                    'critical' => $violationsPending,
                    'title' => 'مخالفات وزارة النقل',
                    'route' => 'transport-violations.index',
                    'route_params' => [],
                ],
                'spare_part_transfers' => [
                    'active' => $sparePartTransfersTotal,
                    'critical' => $sparePartTransfersUnknown,
                    'title' => 'نقل قطع غيار بدون تعميد الإدارة',
                    'route' => 'spare-part-transfers.index',
                    'route_params' => [],
                ],
                'car_breakdowns' => [
                    'active' => $carBreakdownsTotal,
                    'critical' => $carBreakdownsMajor,
                    'title' => 'السيارات المتعطلة بالورش',
                    'route' => 'car-breakdowns.index',
                    'route_params' => [],
                ],
                'idle_project_equipment' => [
                    'active' => $idleProjectEquipmentTotal,
                    'critical' => $idleProjectEquipmentHigh,
                    'title' => 'معدات بدون عمل فعلي بالمشاريع',
                    'route' => 'idle-project-equipment.index',
                    'route_params' => [],
                ],
                'financial_support' => [
                    'active' => $financialSupportTotal,
                    'critical' => $financialSupportUnderRepair,
                    'title' => 'الدعم المالي',
                    'route' => 'financial-support.index',
                    'route_params' => [],
                ],
                'maintenance_deviations' => [
                    'active' => $maintenanceSummary['total_days'],
                    'critical' => 0,
                    'title' => 'انحراف الصيانات',
                    'route' => 'maintenance-deviations.index',
                    'route_params' => [],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, string>  $routeParams
     * @return array{
     *     key: string,
     *     title: string,
     *     short_title: string,
     *     subtitle: string,
     *     route: string,
     *     route_params: array<string, string>,
     *     active_count: int,
     *     critical_count: int,
     *     total_count: int,
     *     status_level: string,
     *     status_label: string,
     *     critical_label: string,
     *     accent: string
     * }
     */
    protected function makeModuleCard(
        string $key,
        string $title,
        string $shortTitle,
        string $subtitle,
        string $route,
        array $routeParams,
        int $activeCount,
        int $criticalCount,
        int $totalCount,
        string $criticalLabel,
        string $accent,
    ): array {
        if ($criticalCount > 0) {
            $statusLevel = 'critical';
            $statusLabel = 'يتطلب تدخل عاجل';
        } elseif ($activeCount > 0) {
            $statusLevel = 'warning';
            $statusLabel = 'قيد المتابعة';
        } else {
            $statusLevel = 'healthy';
            $statusLabel = 'مستقر';
        }

        return [
            'key' => $key,
            'title' => $title,
            'short_title' => $shortTitle,
            'subtitle' => $subtitle,
            'route' => $route,
            'route_params' => $routeParams,
            'active_count' => $activeCount,
            'critical_count' => $criticalCount,
            'total_count' => $totalCount,
            'status_level' => $statusLevel,
            'status_label' => $statusLabel,
            'critical_label' => $criticalLabel,
            'accent' => $accent,
        ];
    }

    /**
     * @return array{labels: list<string>, counts: list<int>, colors: list<string>}
     */
    protected function buildBreakdownsByCategoryChart(): array
    {
        $countsByCategory = EquipmentBreakdown::query()
            ->active()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $labels = [];
        $counts = [];
        $colors = [];

        foreach (BreakdownCategory::cases() as $category) {
            $labels[] = $category->label();
            $counts[] = (int) ($countsByCategory[$category->value] ?? 0);
            $colors[] = $category->chartColor();
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'colors' => $colors,
        ];
    }

    /**
     * Aggregate top 10 highest-priority critical deviations across all 5 modules.
     *
     * @return list<array{
     *     module_key: string,
     *     module_label: string,
     *     identifier: string,
     *     title: string,
     *     detail: string,
     *     severity_label: string,
     *     severity_level: string,
     *     metric_label: string,
     *     priority_score: int,
     *     route: string,
     *     route_params: array<string, string>
     * }>
     */
    protected function buildCriticalFeed(): array
    {
        $items = [];

        // Critical equipment breakdowns
        EquipmentBreakdown::query()
            ->critical()
            ->orderBy('breakdown_date')
            ->limit(6)
            ->get()
            ->each(function (EquipmentBreakdown $breakdown) use (&$items): void {
                $downtime = $breakdown->downtime_days;
                $partsTag = $breakdown->spare_parts_delay ? ' • تأخر قطع غيار' : '';

                $items[] = [
                    'module_key' => 'equipment_breakdowns',
                    'module_label' => 'معدات متعطلة بالورش',
                    'identifier' => $breakdown->equipment_code ?: '—',
                    'title' => sprintf('عطل %s (%s)', $breakdown->category?->label() ?? '—', $breakdown->repair_status?->label() ?? '—'),
                    'detail' => ($breakdown->notes ?: 'عطل حرج يوقف تشغيل المعدة').$partsTag,
                    'severity_label' => $breakdown->severity?->label() ?? 'حرج',
                    'severity_level' => 'critical',
                    'metric_label' => sprintf('متوقفة منذ %d يوم', $downtime),
                    'priority_score' => 100 + $downtime + ($breakdown->spare_parts_delay ? 15 : 0),
                    'route' => 'equipment-breakdowns.index',
                    'route_params' => array_filter([
                        'search' => $breakdown->equipment_code,
                        'severity' => $breakdown->severity?->value,
                    ]),
                ];
            });

        // Expired & missing regulatory documents
        RegulatoryDocument::query()
            ->critical()
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 0 ELSE 1 END, expiry_date ASC')
            ->limit(6)
            ->get()
            ->each(function (RegulatoryDocument $document) use (&$items): void {
                $daysRemaining = $document->days_remaining;
                $isMissing = $document->compliance_status === ComplianceStatus::Missing;
                $overdueDays = $daysRemaining !== null ? abs(min(0, $daysRemaining)) : 30;

                $items[] = [
                    'module_key' => 'regulatory_documents',
                    'module_label' => 'الأوراق النظامية',
                    'identifier' => $document->entity_identifier ?: '—',
                    'title' => sprintf('%s — %s (%s)', $document->document_type ?: 'وثيقة', $document->compliance_status->label(), $document->entity_type?->label() ?? '—'),
                    'detail' => $document->document_number
                        ? sprintf('رقم الوثيقة: %s', $document->document_number)
                        : ($document->notes ?: 'وثيقة نظامية غير متوفرة أو منتهية الصلاحية'),
                    'severity_label' => 'حرج',
                    'severity_level' => 'critical',
                    'metric_label' => $isMissing
                        ? 'وثيقة مفقودة'
                        : sprintf('منتهية منذ %d يوم', $overdueDays),
                    'priority_score' => ($isMissing ? 110 : 95) + $overdueDays,
                    'route' => 'regulatory-documents.index',
                    'route_params' => array_filter([
                        'search' => $document->entity_identifier,
                        'compliance_status' => $document->compliance_status->value,
                    ]),
                ];
            });

        // Delayed or overdue ownership transfers
        OwnershipTransfer::query()
            ->critical()
            ->orderBy('expected_date')
            ->limit(6)
            ->get()
            ->each(function (OwnershipTransfer $transfer) use (&$items): void {
                $delayDays = $transfer->delay_days;

                $items[] = [
                    'module_key' => 'ownership_transfers',
                    'module_label' => 'نقل ملكيات المعدات',
                    'identifier' => $transfer->equipment_code ?: '—',
                    'title' => sprintf('%s (%s ← %s)', $transfer->equipment_type ?: 'معدة', $transfer->target_owner ?: '—', $transfer->current_owner ?: '—'),
                    'detail' => $transfer->blocker_reason ?: 'تجاوز الموعد المتوقع لإتمام نقل الملكية',
                    'severity_label' => $transfer->status === OwnershipTransferStatus::Delayed ? 'حرج' : 'متوسط',
                    'severity_level' => 'critical',
                    'metric_label' => sprintf('متأخر %d يوم', $delayDays),
                    'priority_score' => 85 + $delayDays,
                    'route' => 'ownership-transfers.index',
                    'route_params' => array_filter([
                        'search' => $transfer->equipment_code,
                        'status' => $transfer->status->value,
                    ]),
                ];
            });

        // Long-idle drivers waiting on breakdowns
        BreakdownDriverStoppage::query()
            ->critical()
            ->orderBy('stoppage_start_date')
            ->limit(6)
            ->get()
            ->each(function (BreakdownDriverStoppage $stoppage) use (&$items): void {
                $idleDays = $stoppage->idle_days;

                $items[] = [
                    'module_key' => 'breakdown_driver_stoppages',
                    'module_label' => 'سائقين متوقفين على أعطال',
                    'identifier' => $stoppage->employee_id ?: '—',
                    'title' => sprintf('%s — المعدة %s', $stoppage->driver_name ?: 'سائق', $stoppage->equipment_code ?: '—'),
                    'detail' => $stoppage->notes ?: sprintf('الحالة: %s', $stoppage->status->label()),
                    'severity_label' => 'حرج',
                    'severity_level' => 'critical',
                    'metric_label' => sprintf('متوقف %d يوم', $idleDays),
                    'priority_score' => 80 + $idleDays,
                    'route' => 'breakdown-stoppages.index',
                    'route_params' => array_filter([
                        'search' => $stoppage->employee_id,
                        'status' => $stoppage->status->value,
                    ]),
                ];
            });

        // Unassigned drivers idle >= 14 days or expired license
        UnassignedDriver::query()
            ->critical()
            ->orderBy('unassigned_since')
            ->limit(6)
            ->get()
            ->each(function (UnassignedDriver $driver) use (&$items): void {
                $idleDays = $driver->idle_duration_days;
                $licenseWarning = $driver->is_license_expired ? 'رخصة منتهية الصلاحية' : sprintf('خمول %d يوم', $idleDays);

                $items[] = [
                    'module_key' => 'unassigned_drivers',
                    'module_label' => 'سائقين بدون معدات',
                    'identifier' => $driver->employee_id ?: '—',
                    'title' => sprintf('%s (%s)', $driver->driver_name ?: 'سائق', $driver->license_type ?: '—'),
                    'detail' => $driver->notes ?: sprintf('حالة السائق: %s', $driver->status->label()),
                    'severity_label' => 'حرج',
                    'severity_level' => 'critical',
                    'metric_label' => $licenseWarning,
                    'priority_score' => 75 + $idleDays + ($driver->is_license_expired ? 20 : 0),
                    'route' => 'unassigned-drivers.index',
                    'route_params' => array_filter([
                        'search' => $driver->employee_id,
                        'status' => $driver->status->value,
                    ]),
                ];
            });

        usort(
            $items,
            static fn (array $first, array $second): int => $second['priority_score'] <=> $first['priority_score'],
        );

        return array_slice($items, 0, 10);
    }
}
