<?php

declare(strict_types=1);

use App\Enums\AuditEvent;
use App\Enums\OwnershipTransferStatus;
use App\Models\AuditLog;
use App\Models\OwnershipTransfer;
use App\Models\User;

it('lists, searches, and filters ownership transfers via html and ajax partial endpoints', function (): void {
    $user = User::factory()->operationsManager()->create();

    OwnershipTransfer::factory()->delayed(10)->create([
        'equipment_code' => 'EQ-TR-ALPHA',
        'branch' => 'المدينة المنوره',
        'chassis_number' => 'CTA0422EAMAW01178',
        'blocker_reason' => 'لا يوجد بطاقة جمركية',
    ]);
    OwnershipTransfer::factory()->onTime()->create([
        'equipment_code' => 'EQ-TR-BETA',
        'branch' => 'الجوف',
        'chassis_number' => 'CTA0422EVMAW01180',
    ]);

    $this->actingAs($user)
        ->get(route('ownership-transfers.index'))
        ->assertOk()
        ->assertSee('EQ-TR-ALPHA')
        ->assertSee('CTA0422EAMAW01178')
        ->assertSee('EQ-TR-BETA');

    $this->actingAs($user)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('ownership-transfers.index', ['search' => 'CTA0422EAMAW01178', 'branch' => 'المدينة المنوره', 'status' => 'delayed']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonStructure(['kpis', 'table_html', 'export_url', 'total']);
});

it('auto-flags overdue transfers as delayed and allows saving without any required fields', function (): void {
    $manager = User::factory()->operationsManager()->create();

    // Saving with blank blocker_reason and minimal fields succeeds and auto-flags overdue transfer as delayed
    $this->actingAs($manager)
        ->postJson(route('ownership-transfers.store'), [
            'equipment_code' => 'EQ-OVERDUE-1',
            'expected_date' => today()->subDays(4)->format('Y-m-d'),
            'actual_date' => null,
            'status' => OwnershipTransferStatus::Pending->value,
            'blocker_reason' => '',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'تمت الإضافة بنجاح');

    $overdueTransfer = OwnershipTransfer::query()->where('equipment_code', 'EQ-OVERDUE-1')->firstOrFail();
    expect($overdueTransfer->status)->toBe(OwnershipTransferStatus::Delayed);

    // Providing full details also succeeds and logs Created audit event
    $this->actingAs($manager)
        ->postJson(route('ownership-transfers.store'), [
            'equipment_code' => 'EQ-OVERDUE-2',
            'equipment_type' => 'حفار كاتربيلر',
            'current_owner' => 'قطاع الرياض',
            'target_owner' => 'قطاع جدة',
            'request_date' => today()->subDays(12)->format('Y-m-d'),
            'expected_date' => today()->subDays(4)->format('Y-m-d'),
            'actual_date' => null,
            'status' => OwnershipTransferStatus::Pending->value,
            'blocker_reason' => 'تأخر الفحص الفني',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'تمت الإضافة بنجاح');

    $transfer = OwnershipTransfer::query()->where('equipment_code', 'EQ-OVERDUE-2')->firstOrFail();

    expect($transfer->status)->toBe(OwnershipTransferStatus::Delayed)
        ->and($transfer->auditLogs()->where('event', AuditEvent::Created)->count())->toBe(1);
});

it('updates, soft-deletes, enforces rbac, returns audit details, and exports filtered csv', function (): void {
    $admin = User::factory()->admin()->create();
    $supervisor = User::factory()->fleetSupervisor()->create();
    $viewer = User::factory()->executiveViewer()->create();

    $delayed = OwnershipTransfer::factory()->delayed(6)->create(['equipment_code' => 'EQ-EXP-1']);
    OwnershipTransfer::factory()->onTime()->create(['equipment_code' => 'EQ-EXP-2']);

    // Viewer cannot create
    $this->actingAs($viewer)
        ->postJson(route('ownership-transfers.store'), [])
        ->assertForbidden();

    // Supervisor can update
    $this->actingAs($supervisor)
        ->putJson(route('ownership-transfers.update', $delayed), [
            'equipment_code' => 'EQ-EXP-1',
            'equipment_type' => $delayed->equipment_type,
            'current_owner' => $delayed->current_owner,
            'target_owner' => 'مشروع نيوم المحدث',
            'request_date' => $delayed->request_date->format('Y-m-d'),
            'expected_date' => $delayed->expected_date->format('Y-m-d'),
            'actual_date' => today()->format('Y-m-d'),
            'status' => OwnershipTransferStatus::OnTime->value,
            'blocker_reason' => null,
            'notes' => 'اكتمل النقل',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'تم التحديث بنجاح');

    expect($delayed->fresh()->target_owner)->toBe('مشروع نيوم المحدث')
        ->and($delayed->auditLogs()->where('event', AuditEvent::Updated)->count())->toBe(1);

    // Show endpoint includes audit logs for admin but hides them for viewer
    $this->actingAs($admin)
        ->getJson(route('ownership-transfers.show', $delayed))
        ->assertOk()
        ->assertJsonPath('can_view_audit_logs', true);

    $this->actingAs($viewer)
        ->getJson(route('ownership-transfers.show', $delayed))
        ->assertOk()
        ->assertJsonPath('can_view_audit_logs', false)
        ->assertJsonCount(0, 'audit_logs');

    // Supervisor cannot delete; Admin can soft-delete
    $this->actingAs($supervisor)
        ->deleteJson(route('ownership-transfers.destroy', $delayed))
        ->assertForbidden();

    // Filtered CSV export respects search filter and writes Exported audit log
    $exportResponse = $this->actingAs($admin)
        ->get(route('ownership-transfers.export', ['search' => 'EQ-EXP-1']));

    $exportResponse->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $xlsxBinary = $exportResponse->streamedContent();
    $xlsxText = extractExportText($xlsxBinary);
    expect(str_starts_with($xlsxBinary, 'PK'))->toBeTrue()
        ->and($xlsxText)->toContain('EQ-EXP-1')
        ->and($xlsxText)->not->toContain('EQ-EXP-2');

    expect(AuditLog::query()->where('event', AuditEvent::Exported)->where('auditable_type', OwnershipTransfer::class)->count())->toBe(1);

    // Soft-delete
    $this->actingAs($admin)
        ->deleteJson(route('ownership-transfers.destroy', $delayed))
        ->assertOk()
        ->assertJsonPath('message', 'تم الحذف بنجاح');

    expect($delayed->fresh()->trashed())->toBeTrue()
        ->and($delayed->auditLogs()->where('event', AuditEvent::Deleted)->count())->toBe(1);
});
