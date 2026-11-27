<?php

declare(strict_types=1);

use App\Enums\BreakdownCategory;
use App\Models\BreakdownDriverStoppage;
use App\Models\EquipmentBreakdown;
use App\Models\OwnershipTransfer;
use App\Models\RegulatoryDocument;
use App\Models\UnassignedDriver;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Cache;

it('aggregates kpis, module counts, charts, and critical feed accurately', function (): void {
    // 1. Ownership Transfers: 1 delayed (critical), 1 pending (active non-critical), 1 on_time (inactive)
    OwnershipTransfer::factory()->delayed(11)->create(['equipment_code' => 'EQ-TR-001']);
    OwnershipTransfer::factory()->pending(6)->create();
    OwnershipTransfer::factory()->onTime()->create();

    // 2. Equipment Breakdowns: 1 critical (waiting parts, 15000), 1 medium (5000), 1 minor (1000), 1 repaired (9000 ignored)
    $criticalBreakdown = EquipmentBreakdown::factory()->critical()->create([
        'equipment_code' => 'EQ-BK-001',
        'category' => BreakdownCategory::Hydraulic,
        'estimated_cost' => 15000.00,
    ]);
    EquipmentBreakdown::factory()->medium()->create([
        'category' => BreakdownCategory::Mechanical,
        'estimated_cost' => 5000.00,
    ]);
    EquipmentBreakdown::factory()->minor()->create([
        'category' => BreakdownCategory::Tires,
        'estimated_cost' => 1000.00,
    ]);
    EquipmentBreakdown::factory()->repaired()->create([
        'estimated_cost' => 9000.00,
    ]);

    // 3. Breakdown Driver Stoppages: 1 waiting 10 days (critical), 1 waiting 2 days (minor), 1 resolved (inactive)
    BreakdownDriverStoppage::factory()->forBreakdown($criticalBreakdown)->waiting(10)->create();
    BreakdownDriverStoppage::factory()->waiting(2)->create();
    BreakdownDriverStoppage::factory()->resolved()->create();

    // 4. Unassigned Drivers: 1 ready 20 days (critical), 1 ready 5 days (medium), 1 on_leave (inactive)
    UnassignedDriver::factory()->ready(20)->create();
    UnassignedDriver::factory()->ready(5)->create();
    UnassignedDriver::factory()->onLeave()->create();

    // 5. Regulatory Documents: 1 expired (critical, 1200), 1 missing (critical, 3000), 1 expiring_soon (medium, 800), 1 valid (inactive, 500)
    RegulatoryDocument::factory()->expired(14)->create(['renewal_cost' => 1200.00]);
    RegulatoryDocument::factory()->missing()->create(['renewal_cost' => 3000.00]);
    RegulatoryDocument::factory()->expiringSoon(10)->create(['renewal_cost' => 800.00]);
    RegulatoryDocument::factory()->valid()->create(['renewal_cost' => 500.00]);

    $service = app(DashboardService::class);
    $summary = $service->getSummary();

    // Active totals: Transfers(2) + Stoppages(2) + Unassigned(2) + Breakdowns(3) + Documents(3) = 12
    // Critical totals: Transfers(1) + Stoppages(1) + Unassigned(1) + Breakdowns(1) + Documents(2) = 6
    // Financial exposure: Active breakdowns (15000+5000+1000=21000) + Deviation docs (1200+3000+800=5000) = 26000
    expect($summary['kpis']['total_active_deviations'])->toBe(12)
        ->and($summary['kpis']['total_critical_alerts'])->toBe(6)
        ->and($summary['kpis']['estimated_financial_exposure'])->toBe(26000.0)
        ->and($summary['kpis']['spare_parts_delays'])->toBe(1)
        ->and($summary['kpis']['total_idle_driver_days'])->toBe(12);

    // Chart severity sum must equal total active deviations
    expect(array_sum($summary['charts']['by_severity']['counts']))->toBe(12)
        ->and($summary['charts']['by_severity']['counts'][0])->toBe(6);

    // Critical feed contains all 6 critical items sorted by priority_score descending
    expect($summary['critical_feed'])->toHaveCount(6);
    $scores = array_column($summary['critical_feed'], 'priority_score');
    $sortedScores = $scores;
    rsort($sortedScores);
    expect($scores)->toBe($sortedScores);
});

it('caches dashboard kpis and invalidates cache automatically when tracked models change', function (): void {
    $service = app(DashboardService::class);

    $initialSummary = $service->getSummary();
    expect($initialSummary['kpis']['total_active_deviations'])->toBe(0)
        ->and(Cache::has(DashboardService::CACHE_KEY))->toBeTrue();

    // Creating an auditable model should invalidate the cache automatically
    $transfer = OwnershipTransfer::factory()->delayed(8)->create();
    expect(Cache::has(DashboardService::CACHE_KEY))->toBeFalse();

    $updatedSummary = $service->getSummary();
    expect($updatedSummary['kpis']['total_active_deviations'])->toBe(1)
        ->and($updatedSummary['kpis']['total_critical_alerts'])->toBe(1);

    // Soft-deleting the record invalidates cache and drops active count back to 0
    $transfer->delete();
    expect(Cache::has(DashboardService::CACHE_KEY))->toBeFalse();

    $afterDeleteSummary = $service->getSummary();
    expect($afterDeleteSummary['kpis']['total_active_deviations'])->toBe(0);
});

it('renders executive rtl dashboard with all 5 kpi shortcut cards, charts, and module links', function (): void {
    $user = User::factory()->admin()->create(['name' => 'أ. عبدالله المنصور']);
    EquipmentBreakdown::factory()->critical()->create(['equipment_code' => 'EQ-DASH-999']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('lang="ar" dir="rtl"', false)
        ->assertSee('لوحة المؤشرات الرئيسية')
        ->assertSee('معدات تحتاج إلى نقل ملكية')
        ->assertSee('موقف السائقين المتوقفين')
        ->assertSee('إجمالي السائقين المتوقفين على أعطال المعدات')
        ->assertSee('إجمالي السائقين المتوقفين بدون معدات')
        ->assertSee('نقل ملكيات المعدات')
        ->assertSee('سائقين متوقفين على أعطال')
        ->assertSee('سائقين بدون معدات')
        ->assertSee('موقف الأعطال بالورش')
        ->assertSee('إجمالي المعدات المتعطلة')
        ->assertSee('إجمالي السيارات المتعطلة')
        ->assertSee('إجمالي الباصات المتعطلة')
        ->assertSee('معدات متعطلة بالورش')
        ->assertSee('الأوراق النظامية')
        ->assertSee('موقف الأوراق النظامية للمعدات')
        ->assertSee('فحص دوري منتهي')
        ->assertSee('استمارات منتهية')
        ->assertSee('كروت تشغيل منتهية')
        ->assertSee('تأمين منتهي')
        ->assertSee('موقف الأوراق النظامية للسيارات')
        ->assertSee('فحص دوري منتهي')
        ->assertSee('استمارات منتهية')
        ->assertSee('تأمين منتهي')
        ->assertSee('مخالفات وزارة النقل')
        ->assertSee('إجمالي المخالفات')
        ->assertSee('قيمة المخالفات (ر.س)')
        ->assertSee('نقل قطع غيار بدون تعميد الإدارة')
        ->assertSee('السيارات المتعطلة بالورش')
        ->assertSee('معدات بدون عمل فعلي بالمشاريع')
        ->assertSee('معدات تحتاج إلى تعديل استمارة')
        ->assertSee('معدلات استهلاك الديزل والرقابة على الصرف')
        ->assertSee('تزويد زيوت للمعدات الذي صرفها عالي')
        ->assertSee('إجمالي معدات الإيجار بالمشاريع')
        ->assertSee('تحقيقات إدارة المعدات')
        ->assertSee('التأخر في المستخلصات الشهرية')
        ->assertSee('مستهدفات المشاريع شهر 12')
        ->assertSee('خطة إدارة المعدات')
        ->assertSee('المشاريع التي تحتاج لها تمويل ديزل')
        ->assertSee('لا يوجد')
        ->assertSee('انحراف الصيانات')
        ->assertSee('الصيانة العادية')
        ->assertSee('الصيانة الوقائية')
        ->assertSee('الدعم المالي')
        ->assertSee('مدير النظام')
        ->assertDontSee('أكثر الانحرافات حرجاً')
        ->assertDontSee('moduleDeviationsChart');

    // Verify all 5 operational module endpoints respond cleanly
    foreach ([
        'ownership-transfers.index',
        'breakdown-stoppages.index',
        'unassigned-drivers.index',
        'equipment-breakdowns.index',
        'regulatory-documents.index',
    ] as $routeName) {
        $this->actingAs($user)->get(route($routeName))->assertOk();
    }

    // Verify dashboard deep-linking URLs pre-apply relevant filters cleanly
    $summary = app(DashboardService::class)->getSummary();
    $breakdownCard = collect($summary['modules'])->firstWhere('key', 'equipment_breakdowns');
    expect($breakdownCard['route_params'])->toHaveKey('severity', 'critical');

    $firstFeed = $summary['critical_feed'][0];
    expect($firstFeed['route_params'])->toHaveKey('search', 'EQ-DASH-999');

    $this->actingAs($user)
        ->get(route($firstFeed['route'], $firstFeed['route_params']))
        ->assertOk()
        ->assertSee('EQ-DASH-999');

    // Verify backward-compatible redirect from Phase 1 placeholder URL
    $this->actingAs($user)
        ->get('/breakdown-driver-stoppages')
        ->assertRedirect('/breakdown-stoppages');

    // Verify dashboard cache refresh endpoint
    $this->actingAs($user)
        ->post(route('dashboard.refresh'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status');

    // Verify inline update of custom dashboard metrics directly from the card
    $this->actingAs($user)
        ->patchJson(route('dashboard.metrics.update', 'registration_amendment_needed'), [
            'value' => '19',
        ])
        ->assertOk()
        ->assertJsonPath('raw_value', '19')
        ->assertJsonPath('display_value', '19');

    $this->actingAs($user)
        ->patchJson(route('dashboard.metrics.update', 'cars_expired_periodic_inspection'), [
            'value' => '23',
        ])
        ->assertOk()
        ->assertJsonPath('raw_value', '23')
        ->assertJsonPath('display_value', '23');

    $this->actingAs($user)
        ->patchJson(route('dashboard.metrics.update', 'high_oil_consumption_equipment'), [
            'value' => '7',
        ])
        ->assertOk()
        ->assertJsonPath('raw_value', '7')
        ->assertJsonPath('display_value', '7');

    $this->actingAs($user)
        ->patchJson(route('dashboard.metrics.update', 'total_project_rental_equipment'), [
            'value' => '35',
        ])
        ->assertOk()
        ->assertJsonPath('raw_value', '35')
        ->assertJsonPath('display_value', '35');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('19')
        ->assertSee('23')
        ->assertSee('7')
        ->assertSee('35');
});
