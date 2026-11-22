<?php

declare(strict_types=1);

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\IdleProjectEquipment;
use App\Models\User;
use Database\Seeders\IdleProjectEquipmentRealDataSeeder;

it('lists, searches, filters, and totals idle project equipment days', function (): void {
    $user = User::factory()->operationsManager()->create();

    IdleProjectEquipment::factory()->create([
        'equipment_code' => 'AP_000008',
        'equipment_type' => 'فرادات',
        'driver_name' => 'جعفر علي',
        'branch' => 'ابها',
        'site_location' => 'مشروع احد روفيدة',
        'total_idle_days' => 11,
    ]);

    IdleProjectEquipment::factory()->create([
        'equipment_code' => 'WL_000010',
        'equipment_type' => 'شيولات',
        'driver_name' => 'صقيل انصاري',
        'branch' => 'الرياض',
        'site_location' => 'مشروع الحائر',
        'total_idle_days' => 2,
    ]);

    $this->actingAs($user)
        ->get(route('idle-project-equipment.index'))
        ->assertOk()
        ->assertSee('معدات بدون عمل فعلي بالمشاريع')
        ->assertSee('AP_000008')
        ->assertSee('13 يوم');

    $this->actingAs($user)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('idle-project-equipment.index', ['branch' => 'ابها', 'idle_band' => 'high']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('kpis.high_idle_days', 1)
        ->assertJsonPath('kpis.sum_idle_days', 13);
});

it('creates with optional/blank fields, updates, logs audit events, exports csv, and soft deletes', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson(route('idle-project-equipment.store'), [
            'total_idle_days' => -5,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['total_idle_days']);

    $this->actingAs($admin)
        ->postJson(route('idle-project-equipment.store'), [
            'branch' => 'نجران',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'تمت الإضافة بنجاح');

    $createResponse = $this->actingAs($admin)
        ->postJson(route('idle-project-equipment.store'), [
            'equipment_code' => 'RD_000099',
            'equipment_type' => 'شنيور',
            'driver_name' => 'ارنولد اريزولا',
            'employee_id' => '4303',
            'branch' => 'نجران',
            'site_location' => 'كسارة الضيقة',
            'equipment_status' => 'بدون عمل فعلي',
            'total_idle_days' => 6,
        ])
        ->assertCreated();

    $item = IdleProjectEquipment::query()->findOrFail($createResponse->json('data.id'));

    $this->actingAs($admin)
        ->getJson(route('idle-project-equipment.show', $item))
        ->assertOk()
        ->assertJsonPath('can_view_audit_logs', true)
        ->assertJsonPath('record.equipment_code', 'RD_000099');

    $this->actingAs($admin)
        ->putJson(route('idle-project-equipment.update', $item), [
            'equipment_code' => 'RD_000099',
            'total_idle_days' => 9,
        ])
        ->assertOk()
        ->assertJsonPath('message', 'تم التحديث بنجاح');

    expect($item->fresh()->total_idle_days)->toBe(9)
        ->and($item->auditLogs()->where('event', AuditEvent::Updated)->count())->toBe(1);

    $csv = $this->actingAs($admin)
        ->get(route('idle-project-equipment.export', ['search' => 'RD_000099']))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('RD_000099')
        ->and(AuditLog::query()->where('event', AuditEvent::Exported)->where('auditable_type', IdleProjectEquipment::class)->count())->toBe(1);

    $this->actingAs($admin)
        ->deleteJson(route('idle-project-equipment.destroy', $item))
        ->assertOk()
        ->assertJsonPath('message', 'تم الحذف بنجاح');

    expect($item->fresh()->trashed())->toBeTrue();
});

it('enforces rbac permissions on idle project equipment endpoints', function (): void {
    $viewer = User::factory()->executiveViewer()->create();
    $supervisor = User::factory()->fleetSupervisor()->create();
    $item = IdleProjectEquipment::factory()->create();

    $this->actingAs($viewer)
        ->get(route('idle-project-equipment.index'))
        ->assertOk();

    $this->actingAs($viewer)
        ->postJson(route('idle-project-equipment.store'), ['equipment_code' => 'AP_000900'])
        ->assertForbidden();

    $this->actingAs($supervisor)
        ->postJson(route('idle-project-equipment.store'), ['equipment_code' => 'AP_000901'])
        ->assertCreated();

    $this->actingAs($supervisor)
        ->deleteJson(route('idle-project-equipment.destroy', $item))
        ->assertForbidden();
});

it('seeds all 44 real idle project equipment records accurately', function (): void {
    $this->seed(IdleProjectEquipmentRealDataSeeder::class);

    expect(IdleProjectEquipment::query()->count())->toBe(44)
        ->and((int) IdleProjectEquipment::query()->sum('total_idle_days'))->toBe(269)
        ->and(IdleProjectEquipment::query()->where('equipment_code', 'AP_000008')->exists())->toBeTrue()
        ->and(IdleProjectEquipment::query()->where('equipment_code', 'RD_000003')->exists())->toBeTrue();
});
