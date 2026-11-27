<?php

declare(strict_types=1);

use App\Enums\AuditEvent;
use App\Enums\UnassignedDriverStatus;
use App\Models\AuditLog;
use App\Models\UnassignedDriver;
use App\Models\User;

it('lists, filters by license state, and highlights expired or approaching license expiry', function (): void {
    $user = User::factory()->operationsManager()->create();

    UnassignedDriver::factory()->expiredLicense()->create([
        'driver_name' => 'سائق رخصة منتهية',
        'employee_id' => 'DRV-EXP-1',
        'location' => 'نجران',
    ]);
    UnassignedDriver::factory()->ready(4)->create([
        'driver_name' => 'سائق رخصة قاربت',
        'employee_id' => 'DRV-SOON-2',
        'location' => 'حائل',
        'license_expiry' => today()->addDays(10),
    ]);
    UnassignedDriver::factory()->onLeave()->create([
        'driver_name' => 'سائق بإجازة',
        'employee_id' => 'DRV-OK-3',
        'location' => 'المدينة',
    ]);

    $this->actingAs($user)
        ->get(route('unassigned-drivers.index'))
        ->assertOk()
        ->assertSee('رخصة منتهية')
        ->assertSee('تنتهي خلال 10 يوم');

    $this->actingAs($user)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('unassigned-drivers.index', ['license_state' => 'expired', 'location' => 'نجران']))
        ->assertOk()
        ->assertJsonPath('total', 1);

    $this->actingAs($user)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('unassigned-drivers.index', ['license_state' => 'expiring_soon']))
        ->assertOk()
        ->assertJsonPath('total', 1);
});

it('supports quick status toggle, crud workflows, audit logs, rbac, and csv export', function (): void {
    $admin = User::factory()->admin()->create();
    $supervisor = User::factory()->fleetSupervisor()->create();
    $viewer = User::factory()->executiveViewer()->create();

    // Invalid date format rejected, while saving minimal/empty fields succeeds
    $this->actingAs($supervisor)
        ->postJson(route('unassigned-drivers.store'), ['license_expiry' => 'not-a-date'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['license_expiry']);

    $this->actingAs($supervisor)
        ->postJson(route('unassigned-drivers.store'), ['driver_name' => 'سائق بدون حقول إلزامية'])
        ->assertCreated();

    // Valid creation
    $this->actingAs($supervisor)
        ->postJson(route('unassigned-drivers.store'), [
            'driver_name' => 'مازن فهد العتيبي',
            'employee_id' => 'DRV-8810',
            'license_type' => 'رخصة معدات ثقيلة',
            'license_expiry' => today()->addMonths(6)->format('Y-m-d'),
            'unassigned_since' => today()->subDays(8)->format('Y-m-d'),
            'status' => UnassignedDriverStatus::Ready->value,
            'notes' => 'متاح للعمل بالمنطقة الوسطى',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'تمت الإضافة بنجاح');

    $driver = UnassignedDriver::query()->where('employee_id', 'DRV-8810')->firstOrFail();

    // Quick status toggle authorized for supervisor, forbidden for viewer
    $this->actingAs($viewer)
        ->patchJson(route('unassigned-drivers.update-status', $driver), [
            'status' => UnassignedDriverStatus::Evaluation->value,
        ])
        ->assertForbidden();

    $this->actingAs($supervisor)
        ->patchJson(route('unassigned-drivers.update-status', $driver), [
            'status' => UnassignedDriverStatus::Evaluation->value,
        ])
        ->assertOk()
        ->assertJsonPath('message', 'تم التحديث بنجاح');

    expect($driver->fresh()->status)->toBe(UnassignedDriverStatus::Evaluation)
        ->and($driver->auditLogs()->where('event', AuditEvent::Updated)->count())->toBe(1);

    // Full update
    $this->actingAs($admin)
        ->putJson(route('unassigned-drivers.update', $driver), [
            'driver_name' => 'مازن فهد العتيبي',
            'employee_id' => 'DRV-8810',
            'license_type' => 'رخصة تشغيل رافعات',
            'license_expiry' => today()->addYear()->format('Y-m-d'),
            'unassigned_since' => today()->subDays(8)->format('Y-m-d'),
            'status' => UnassignedDriverStatus::OnLeave->value,
            'notes' => 'إجازة معتمدة',
        ])
        ->assertOk();

    expect($driver->fresh()->license_type)->toBe('رخصة تشغيل رافعات');

    // Export CSV
    $csv = $this->actingAs($admin)
        ->get(route('unassigned-drivers.export', ['status' => 'on_leave']))
        ->assertOk()
        ->streamedContent();

    expect(extractExportText($csv))->toContain('DRV-8810')
        ->and(AuditLog::query()->where('event', AuditEvent::Exported)->where('auditable_type', UnassignedDriver::class)->count())->toBe(1);

    // Soft-delete
    $this->actingAs($admin)
        ->deleteJson(route('unassigned-drivers.destroy', $driver))
        ->assertOk()
        ->assertJsonPath('message', 'تم الحذف بنجاح');

    expect($driver->fresh()->trashed())->toBeTrue();
});
