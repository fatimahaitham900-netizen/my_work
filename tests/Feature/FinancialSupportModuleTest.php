<?php

declare(strict_types=1);

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\FinancialSupportMonth;
use App\Models\FinancialSupportTransfer;
use App\Models\User;
use Database\Seeders\FinancialSupportRealDataSeeder;

it('seeds all 4 monthly summaries and 110 transfer records matching official pdf totals', function (): void {
    $this->seed(FinancialSupportRealDataSeeder::class);

    expect(FinancialSupportMonth::query()->count())->toBe(4)
        ->and(FinancialSupportTransfer::query()->count())->toBe(110)
        ->and((int) round((float) FinancialSupportTransfer::query()->where('support_month', 8)->sum('amount')))->toBe(132066)
        ->and((int) round((float) FinancialSupportTransfer::query()->where('support_month', 9)->sum('amount')))->toBe(106621)
        ->and((int) round((float) FinancialSupportTransfer::query()->where('support_month', 10)->sum('amount')))->toBe(118747)
        ->and((int) round((float) FinancialSupportMonth::query()->sum('repair_support_amount')))->toBe(406629)
        ->and((int) round((float) FinancialSupportMonth::query()->sum('custody_amount')))->toBe(298354)
        ->and((int) round((float) FinancialSupportMonth::query()->sum('not_transferred_amount')))->toBe(56608)
        ->and((int) round((float) FinancialSupportTransfer::query()->where('support_month', 11)->where('transfer_stage', 'لم يتم التحويل')->sum('amount')))->toBe(56608)
        ->and((int) FinancialSupportMonth::query()->sum('total_equipment_count'))->toBe(90)
        ->and((int) FinancialSupportMonth::query()->sum('repaired_count'))->toBe(80)
        ->and((int) FinancialSupportMonth::query()->sum('under_repair_count'))->toBe(10);
});

it('renders financial support monthly tabs, deep-links under-repair counts per month, and displays dashboard summary table', function (): void {
    $this->seed(FinancialSupportRealDataSeeder::class);
    $user = User::factory()->operationsManager()->create();

    $month10Link = route('financial-support.index', ['month' => 10, 'repair_status' => 'لم يتم الاصلاح']);
    $month11Link = route('financial-support.index', ['month' => 11, 'repair_status' => 'لم يتم الاصلاح']);
    $month11NotTransferredLink = route('financial-support.index', ['month' => 11, 'transfer_stage' => 'لم يتم التحويل']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('موقف الدعم المالي للمعدات 2026')
        ->assertSee('704,983')
        ->assertSee('406,629')
        ->assertSee('298,354')
        ->assertSee('56,608')
        ->assertSee($month10Link)
        ->assertSee($month11Link)
        ->assertSee($month11NotTransferredLink);

    $this->actingAs($user)
        ->get(route('financial-support.index', ['month' => 9]))
        ->assertOk()
        ->assertSee('الدعم المالي')
        ->assertSee('PO-0005542')
        ->assertSee('106,621');

    // Clicking Month 10 under-repair count (3) auto-filters to Month 10's 3 under-repair items
    $this->actingAs($user)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get($month10Link)
        ->assertOk()
        ->assertJsonPath('total', 3);

    // Clicking Month 11 under-repair count auto-filters to Month 11's under-repair items
    $this->actingAs($user)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get($month11Link)
        ->assertOk()
        ->assertJsonPath('total', 7);

    $this->actingAs($user)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('financial-support.index', ['month' => 11, 'transfer_stage' => 'لم يتم التحويل']))
        ->assertOk()
        ->assertJsonPath('total', 9);
});

it('creates with optional fields, updates transfer and month summary, logs audits, exports csv, and soft deletes', function (): void {
    $this->seed(FinancialSupportRealDataSeeder::class);
    $admin = User::factory()->admin()->create();

    // Blank/minimal creation succeeds
    $this->actingAs($admin)
        ->postJson(route('financial-support.store'), [
            'support_month' => 11,
            'branch' => 'نجران',
        ])
        ->assertCreated();

    $createResponse = $this->actingAs($admin)
        ->postJson(route('financial-support.store'), [
            'support_month' => 11,
            'equipment_type' => 'شيول',
            'equipment_code' => 'WL-999',
            'amount' => 4500,
            'beneficiary_name' => 'اتحاد الخير',
            'branch' => 'نجران',
            'transfer_stage' => 'تم التحويل',
            'repair_status' => 'تم الاصلاح',
        ])
        ->assertCreated();

    $transfer = FinancialSupportTransfer::query()->findOrFail($createResponse->json('data.id'));

    $this->actingAs($admin)
        ->putJson(route('financial-support.update', $transfer), [
            'support_month' => 11,
            'equipment_code' => 'WL-999',
            'amount' => 5200,
        ])
        ->assertOk();

    expect((float) $transfer->fresh()->amount)->toBe(5200.0)
        ->and($transfer->auditLogs()->where('event', AuditEvent::Updated)->count())->toBe(1);

    // Update monthly summary custody amount
    $month11 = FinancialSupportMonth::query()->where('month', 11)->firstOrFail();

    $this->actingAs($admin)
        ->putJson(route('financial-support.months.update', $month11), [
            'repair_support_amount' => 49195,
            'custody_amount' => 50000,
            'total_equipment_count' => 13,
            'repaired_count' => 8,
            'under_repair_count' => 5,
        ])
        ->assertOk();

    expect((float) $month11->fresh()->custody_amount)->toBe(50000.0);

    $csv = $this->actingAs($admin)
        ->get(route('financial-support.export', ['month' => 11, 'search' => 'WL-999']))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('WL-999')
        ->and(AuditLog::query()->where('event', AuditEvent::Exported)->where('auditable_type', FinancialSupportTransfer::class)->count())->toBe(1);

    $this->actingAs($admin)
        ->deleteJson(route('financial-support.destroy', $transfer))
        ->assertOk();

    expect($transfer->fresh()->trashed())->toBeTrue();
});

it('enforces rbac permissions on financial support endpoints', function (): void {
    $this->seed(FinancialSupportRealDataSeeder::class);
    $viewer = User::factory()->executiveViewer()->create();
    $supervisor = User::factory()->fleetSupervisor()->create();
    $transfer = FinancialSupportTransfer::query()->firstOrFail();

    $this->actingAs($viewer)
        ->get(route('financial-support.index'))
        ->assertOk();

    $this->actingAs($viewer)
        ->postJson(route('financial-support.store'), ['equipment_code' => 'WL-777'])
        ->assertForbidden();

    $this->actingAs($supervisor)
        ->postJson(route('financial-support.store'), ['equipment_code' => 'WL-778'])
        ->assertCreated();

    $this->actingAs($supervisor)
        ->deleteJson(route('financial-support.destroy', $transfer))
        ->assertForbidden();
});
