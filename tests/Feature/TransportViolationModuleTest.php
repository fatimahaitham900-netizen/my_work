<?php

declare(strict_types=1);

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\TransportViolation;
use App\Models\User;
use Database\Seeders\TransportViolationsRealDataSeeder;

it('lists, searches, multi-filters, and computes filtered totals for transport violations', function (): void {
    $user = User::factory()->operationsManager()->create();

    TransportViolation::factory()->create([
        'violation_number' => '1000009990001',
        'record_holder' => 'الموظف',
        'violation_date' => '2026-10-06',
        'amount' => 100000,
        'department' => 'المعدات',
        'city' => 'نجران',
        'objection_status' => 'تم عمل اعتراض وفي انتظار النتيجه',
        'equipment_code' => 'TR-116',
        'driver_name' => 'نور الله جان',
        'employee_id' => '6104',
        'employee_status' => 'متواجد على رأس العمل',
    ]);

    TransportViolation::factory()->create([
        'violation_number' => '1000009990002',
        'record_holder' => 'المسار الحديث للمقاولات',
        'violation_date' => '2024-02-14',
        'amount' => 2400,
        'department' => 'الانتاج',
        'city' => 'الرياض',
        'objection_status' => 'تم عمل اعتراض وتم الرفض',
        'equipment_code' => 'TR-044',
        'driver_name' => 'اشور ثابا',
        'employee_id' => '4942',
        'employee_status' => 'خروج نهائي',
    ]);

    $this->actingAs($user)
        ->get(route('transport-violations.index'))
        ->assertOk()
        ->assertSee('مخالفات وزارة النقل')
        ->assertSee('1000009990001')
        ->assertSee('1000009990002')
        ->assertSee('102,400');

    $this->actingAs($user)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('transport-violations.index', [
            'record_holder' => 'الموظف',
            'department' => 'المعدات',
            'city' => 'نجران',
            'employee_status' => 'متواجد على رأس العمل',
        ]))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('kpis.pending_objection', 1)
        ->assertJsonPath('kpis.rejected_or_missed', 1);
});

it('creates with optional/blank fields, updates, logs audit events, exports csv, and soft deletes', function (): void {
    $admin = User::factory()->admin()->create();

    // Negative amount rejected
    $this->actingAs($admin)
        ->postJson(route('transport-violations.store'), [
            'amount' => -500,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);

    // Minimal/blank submission succeeds (no required fields rule)
    $this->actingAs($admin)
        ->postJson(route('transport-violations.store'), [
            'city' => 'نجران',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'تمت الإضافة بنجاح');

    // Full creation
    $createResponse = $this->actingAs($admin)
        ->postJson(route('transport-violations.store'), [
            'violation_number' => '1000008887776',
            'record_holder' => 'الموظف',
            'violation_date' => '2026-10-15',
            'amount' => 15000,
            'department' => 'المعدات',
            'city' => 'مكة',
            'violation_description' => 'تجاوز الوزن الإجمالي المسموح به',
            'objection_status' => 'تم عمل اعتراض وفي انتظار النتيجه',
            'plate_number' => 'أ ر و 9526',
            'equipment_code' => 'TR-099',
            'driver_name' => 'عبد القادر اشتياق',
            'employee_id' => '92',
            'employee_status' => 'متواجد على رأس العمل',
            'liability' => 'الشركة',
            'permit_status' => 'يوجد تصريح',
        ])
        ->assertCreated();

    $violation = TransportViolation::query()->findOrFail($createResponse->json('data.id'));

    // Detail endpoint includes audit logs for admin
    $this->actingAs($admin)
        ->getJson(route('transport-violations.show', $violation))
        ->assertOk()
        ->assertJsonPath('can_view_audit_logs', true)
        ->assertJsonPath('record.violation_number', '1000008887776');

    // Update with partial payload
    $this->actingAs($admin)
        ->putJson(route('transport-violations.update', $violation), [
            'violation_number' => '1000008887776',
            'amount' => 12000,
            'objection_status' => 'تم عمل اعتراض وتم الرفض',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'تم التحديث بنجاح');

    expect((float) $violation->fresh()->amount)->toBe(12000.0)
        ->and($violation->auditLogs()->where('event', AuditEvent::Updated)->count())->toBe(1);

    // Export CSV respects filters and records Exported audit event
    $csv = $this->actingAs($admin)
        ->get(route('transport-violations.export', ['search' => '1000008887776']))
        ->assertOk()
        ->streamedContent();

    expect(extractExportText($csv))->toContain('1000008887776')
        ->and(AuditLog::query()->where('event', AuditEvent::Exported)->where('auditable_type', TransportViolation::class)->count())->toBe(1);

    // Soft delete
    $this->actingAs($admin)
        ->deleteJson(route('transport-violations.destroy', $violation))
        ->assertOk()
        ->assertJsonPath('message', 'تم الحذف بنجاح');

    expect($violation->fresh()->trashed())->toBeTrue();
});

it('enforces rbac permissions on transport violations endpoints', function (): void {
    $viewer = User::factory()->executiveViewer()->create();
    $supervisor = User::factory()->fleetSupervisor()->create();
    $violation = TransportViolation::factory()->create();

    $this->actingAs($viewer)
        ->get(route('transport-violations.index'))
        ->assertOk();

    $this->actingAs($viewer)
        ->postJson(route('transport-violations.store'), ['violation_number' => '999999'])
        ->assertForbidden();

    $this->actingAs($supervisor)
        ->postJson(route('transport-violations.store'), ['violation_number' => '888888'])
        ->assertCreated();

    $this->actingAs($supervisor)
        ->deleteJson(route('transport-violations.destroy', $violation))
        ->assertForbidden();
});

it('seeds all 197 real transport violation records matching the official pdf grand total', function (): void {
    $this->seed(TransportViolationsRealDataSeeder::class);

    expect(TransportViolation::query()->count())->toBe(197)
        ->and((int) round((float) TransportViolation::query()->sum('amount')))->toBe(1312800);
});
