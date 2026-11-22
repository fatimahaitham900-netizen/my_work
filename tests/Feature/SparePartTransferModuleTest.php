<?php

declare(strict_types=1);

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\SparePartTransfer;
use App\Models\User;
use Database\Seeders\SparePartTransfersRealDataSeeder;

it('lists, searches, and filters spare part transfers including unknown sources', function (): void {
    $user = User::factory()->operationsManager()->create();

    SparePartTransfer::factory()->create([
        'item_description' => 'نقل طرمبة هيدروليك من كرين 14 الى كرين 6',
        'from_equipment' => 'كرين 14',
        'to_equipment' => 'كرين 6',
        'transfer_date' => '2026-08-05',
    ]);

    SparePartTransfer::factory()->unknownSource()->create([
        'transfer_date' => '2026-08-24',
    ]);

    $this->actingAs($user)
        ->get(route('spare-part-transfers.index'))
        ->assertOk()
        ->assertSee('نقل قطع غيار بدون تعميد الإدارة')
        ->assertSee('نقل طرمبة هيدروليك من كرين 14 الى كرين 6')
        ->assertSee('غير معروفة');

    $this->actingAs($user)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('spare-part-transfers.index', ['source_status' => 'unknown']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('kpis.unknown_source', 1);
});

it('creates with optional/blank fields, updates, logs audit events, exports csv, and soft deletes', function (): void {
    $admin = User::factory()->admin()->create();

    // Minimal submission with blank optional fields succeeds
    $this->actingAs($admin)
        ->postJson(route('spare-part-transfers.store'), [
            'notes' => 'نقل عاجل للورشة',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'تمت الإضافة بنجاح');

    // Full creation
    $createResponse = $this->actingAs($admin)
        ->postJson(route('spare-part-transfers.store'), [
            'item_description' => 'نقل مبرد زيت( كولر) من مولد 33 الى مولد 35 مكة',
            'from_equipment' => 'مولد 33',
            'to_equipment' => 'مولد 35 مكة',
            'transfer_date' => '2026-09-09',
            'notes' => 'عدد 2كولد',
        ])
        ->assertCreated();

    $transfer = SparePartTransfer::query()->findOrFail($createResponse->json('data.id'));

    $this->actingAs($admin)
        ->getJson(route('spare-part-transfers.show', $transfer))
        ->assertOk()
        ->assertJsonPath('can_view_audit_logs', true)
        ->assertJsonPath('record.from_equipment', 'مولد 33');

    $this->actingAs($admin)
        ->putJson(route('spare-part-transfers.update', $transfer), [
            'item_description' => 'نقل مبرد زيت( كولر) من مولد 33 الى مولد 35 مكة',
            'from_equipment' => 'مولد 33',
            'to_equipment' => 'مولد 35 مكة',
            'notes' => 'تم التسليم والتركيب',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'تم التحديث بنجاح');

    expect($transfer->fresh()->notes)->toBe('تم التسليم والتركيب')
        ->and($transfer->auditLogs()->where('event', AuditEvent::Updated)->count())->toBe(1);

    $csv = $this->actingAs($admin)
        ->get(route('spare-part-transfers.export', ['search' => 'مولد 35']))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('مولد 35 مكة')
        ->and(AuditLog::query()->where('event', AuditEvent::Exported)->where('auditable_type', SparePartTransfer::class)->count())->toBe(1);

    $this->actingAs($admin)
        ->deleteJson(route('spare-part-transfers.destroy', $transfer))
        ->assertOk()
        ->assertJsonPath('message', 'تم الحذف بنجاح');

    expect($transfer->fresh()->trashed())->toBeTrue();
});

it('enforces rbac permissions on spare part transfers endpoints', function (): void {
    $viewer = User::factory()->executiveViewer()->create();
    $supervisor = User::factory()->fleetSupervisor()->create();
    $transfer = SparePartTransfer::factory()->create();

    $this->actingAs($viewer)
        ->get(route('spare-part-transfers.index'))
        ->assertOk();

    $this->actingAs($viewer)
        ->postJson(route('spare-part-transfers.store'), ['item_description' => 'تجربة'])
        ->assertForbidden();

    $this->actingAs($supervisor)
        ->postJson(route('spare-part-transfers.store'), ['item_description' => 'نقل قطعة'])
        ->assertCreated();

    $this->actingAs($supervisor)
        ->deleteJson(route('spare-part-transfers.destroy', $transfer))
        ->assertForbidden();
});

it('seeds all 11 real spare part transfer records accurately', function (): void {
    $this->seed(SparePartTransfersRealDataSeeder::class);

    expect(SparePartTransfer::query()->count())->toBe(11)
        ->and(SparePartTransfer::query()->unknownSource()->count())->toBe(1)
        ->and(SparePartTransfer::query()->where('notes', 'عدد 2كولد')->count())->toBe(1);
});
