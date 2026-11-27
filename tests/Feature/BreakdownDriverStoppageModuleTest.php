<?php

declare(strict_types=1);

use App\Enums\AuditEvent;
use App\Enums\DriverStoppageStatus;
use App\Models\AuditLog;
use App\Models\BreakdownDriverStoppage;
use App\Models\EquipmentBreakdown;
use App\Models\User;

it('lists, searches, filters, and highlights idle_days > 3 in red for breakdown stoppages', function (): void {
    $user = User::factory()->operationsManager()->create();
    $breakdown = EquipmentBreakdown::factory()->critical()->create(['equipment_code' => 'EQ-STP-900']);

    BreakdownDriverStoppage::factory()->forBreakdown($breakdown)->waiting(6)->create([
        'driver_name' => 'خالد متوقف طويلاً',
        'employee_id' => 'EMP-9001',
        'branch' => 'مكة',
        'workshop_status' => 'توريد قطع',
    ]);
    BreakdownDriverStoppage::factory()->resolved()->create([
        'driver_name' => 'سائق عاد للعمل',
        'employee_id' => 'EMP-9002',
        'branch' => 'نجران',
        'workshop_status' => 'تم الاصلاح',
    ]);

    $this->actingAs($user)
        ->get(route('breakdown-stoppages.index'))
        ->assertOk()
        ->assertSee('خالد متوقف طويلاً')
        ->assertSee('توريد قطع')
        ->assertSee('bg-rose-100 text-rose-800', false);

    $this->actingAs($user)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('breakdown-stoppages.index', ['idle_threshold' => 'over_3', 'branch' => 'مكة', 'workshop_status' => 'توريد قطع']))
        ->assertOk()
        ->assertJsonPath('total', 1);
});

it('creates with optional breakdown link, validates invalid inputs, updates, soft-deletes, and exports csv', function (): void {
    $admin = User::factory()->admin()->create();
    $viewer = User::factory()->executiveViewer()->create();
    $breakdown = EquipmentBreakdown::factory()->critical()->create(['equipment_code' => 'EQ-LINK-55']);

    // Invalid format payload (non-existent breakdown_id & resume date earlier than start date)
    $this->actingAs($admin)
        ->postJson(route('breakdown-stoppages.store'), [
            'driver_name' => '',
            'breakdown_id' => 999999,
            'stoppage_start_date' => today()->format('Y-m-d'),
            'expected_resume_date' => today()->subDays(2)->format('Y-m-d'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['breakdown_id', 'expected_resume_date']);

    // Partial update with empty optional fields succeeds without required blockers
    $partial = BreakdownDriverStoppage::factory()->create();
    $this->actingAs($admin)
        ->putJson(route('breakdown-stoppages.update', $partial), [
            'driver_name' => 'سائق محدث جزئياً',
        ])
        ->assertOk();

    // Valid creation linked to active breakdown
    $this->actingAs($admin)
        ->postJson(route('breakdown-stoppages.store'), [
            'driver_name' => 'ناصر عبدالله الحربي',
            'employee_id' => 'EMP-7741',
            'phone' => '0554433221',
            'equipment_code' => 'EQ-LINK-55',
            'breakdown_id' => $breakdown->id,
            'stoppage_start_date' => today()->subDays(5)->format('Y-m-d'),
            'expected_resume_date' => today()->addDays(2)->format('Y-m-d'),
            'status' => DriverStoppageStatus::Waiting->value,
            'notes' => 'بانتظار انتهاء الورشة',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'تمت الإضافة بنجاح');

    $stoppage = BreakdownDriverStoppage::query()->where('employee_id', 'EMP-7741')->firstOrFail();
    expect($stoppage->breakdown_id)->toBe($breakdown->id)
        ->and($stoppage->idle_days)->toBe(5)
        ->and($stoppage->auditLogs()->where('event', AuditEvent::Created)->count())->toBe(1);

    // Update status to reassigned
    $this->actingAs($admin)
        ->putJson(route('breakdown-stoppages.update', $stoppage), [
            'driver_name' => 'ناصر عبدالله الحربي',
            'employee_id' => 'EMP-7741',
            'phone' => '0554433221',
            'equipment_code' => 'EQ-LINK-55',
            'breakdown_id' => $breakdown->id,
            'stoppage_start_date' => $stoppage->stoppage_start_date->format('Y-m-d'),
            'expected_resume_date' => today()->format('Y-m-d'),
            'status' => DriverStoppageStatus::Reassigned->value,
            'notes' => 'تم توفير معدة بديلة',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'تم التحديث بنجاح');

    expect($stoppage->fresh()->status)->toBe(DriverStoppageStatus::Reassigned);

    // CSV export
    $csv = $this->actingAs($admin)
        ->get(route('breakdown-stoppages.export', ['search' => 'EMP-7741']))
        ->assertOk()
        ->streamedContent();

    expect(extractExportText($csv))->toContain('EMP-7741')
        ->and(AuditLog::query()->where('event', AuditEvent::Exported)->where('auditable_type', BreakdownDriverStoppage::class)->count())->toBe(1);

    // Viewer cannot delete; Admin can soft-delete
    $this->actingAs($viewer)->deleteJson(route('breakdown-stoppages.destroy', $stoppage))->assertForbidden();
    $this->actingAs($admin)->deleteJson(route('breakdown-stoppages.destroy', $stoppage))->assertOk();

    expect($stoppage->fresh()->trashed())->toBeTrue();
});
