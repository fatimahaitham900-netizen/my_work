<?php

declare(strict_types=1);

use App\Enums\AuditEvent;
use App\Enums\BreakdownCategory;
use App\Enums\BreakdownSeverity;
use App\Enums\DriverStoppageStatus;
use App\Enums\RepairStatus;
use App\Models\AuditLog;
use App\Models\BreakdownDriverStoppage;
use App\Models\EquipmentBreakdown;
use App\Models\User;

it('lists, filters by category/severity/spare_parts_delay, and highlights spare parts warning badge', function (): void {
    $user = User::factory()->operationsManager()->create();

    EquipmentBreakdown::factory()->critical()->create([
        'equipment_code' => 'EQ-HYD-700',
        'equipment_name' => 'مولد',
        'brand' => 'CAT',
        'workshop_name' => 'ورشه نجران',
        'workshop_status' => 'دعم مالي',
        'category' => BreakdownCategory::Hydraulic,
        'spare_parts_delay' => true,
    ]);
    EquipmentBreakdown::factory()->minor()->create([
        'equipment_code' => 'EQ-TIR-701',
        'equipment_name' => 'شيول',
        'brand' => 'KOMATSU',
        'workshop_name' => 'ورشه المدينة',
        'workshop_status' => 'قيد الاصلاح',
        'category' => BreakdownCategory::Tires,
        'spare_parts_delay' => false,
    ]);

    $this->actingAs($user)
        ->get(route('equipment-breakdowns.index'))
        ->assertOk()
        ->assertSee('EQ-HYD-700')
        ->assertSee('ورشه نجران')
        ->assertSee('عرض حسب الموقف (الموقف × الورش)')
        ->assertSee('النسبة من الإجمالي')
        ->assertSee('عرض حسب نوع المعدة (المعدة × الورش)')
        ->assertSee('توزيع المعدات المتعطلة حسب الموقف والورش والنسبة من الإجمالي')
        ->assertSee('50%')
        ->assertSee('100%')
        ->assertSee('توزيع المعدات المتعطلة حسب نوع المعدة والورش')
        ->assertSee('تأخر قطع غيار');

    $this->actingAs($user)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('equipment-breakdowns.index', ['workshop_name' => 'ورشه نجران', 'equipment_name' => 'مولد', 'brand' => 'CAT', 'workshop_status' => 'دعم مالي', 'category' => 'hydraulic', 'spare_parts_delay' => '1']))
        ->assertOk()
        ->assertJsonPath('total', 1);
});

it('automatically resolves linked waiting drivers with audit logs when marking breakdown as repaired', function (): void {
    $ops = User::factory()->operationsManager()->create();

    $breakdown = EquipmentBreakdown::factory()->critical()->create([
        'equipment_code' => 'EQ-CAS-500',
        'repair_status' => RepairStatus::WaitingParts,
    ]);
    $waitingStoppage = BreakdownDriverStoppage::factory()->forBreakdown($breakdown)->waiting(8)->create();
    $alreadyReassigned = BreakdownDriverStoppage::factory()->forBreakdown($breakdown)->reassigned()->create();

    // Show endpoint surfaces linked waiting stoppages for the prompt UI
    $this->actingAs($ops)
        ->getJson(route('equipment-breakdowns.show', $breakdown))
        ->assertOk()
        ->assertJsonPath('record.waiting_stoppages_count', 1);

    // Mark repaired with resolve_linked_stoppages = true
    $this->actingAs($ops)
        ->putJson(route('equipment-breakdowns.update', $breakdown), [
            'equipment_code' => $breakdown->equipment_code,
            'category' => $breakdown->category->value,
            'severity' => $breakdown->severity->value,
            'breakdown_date' => $breakdown->breakdown_date->format('Y-m-d'),
            'workshop_entry_date' => $breakdown->workshop_entry_date?->format('Y-m-d'),
            'expected_repair_date' => today()->format('Y-m-d'),
            'repair_status' => RepairStatus::Repaired->value,
            'spare_parts_delay' => false,
            'estimated_cost' => 12500,
            'notes' => 'تم الإصلاح والتسليم',
            'resolve_linked_stoppages' => true,
            'linked_stoppage_status' => DriverStoppageStatus::Resolved->value,
        ])
        ->assertOk()
        ->assertJsonPath('resolved_stoppages_count', 1);

    expect($breakdown->fresh()->repair_status)->toBe(RepairStatus::Repaired)
        ->and($waitingStoppage->fresh()->status)->toBe(DriverStoppageStatus::Resolved)
        ->and($alreadyReassigned->fresh()->status)->toBe(DriverStoppageStatus::Reassigned)
        ->and($waitingStoppage->auditLogs()->where('event', AuditEvent::Updated)->count())->toBe(1);
});

it('validates, creates, soft-deletes, and exports filtered equipment breakdowns csv', function (): void {
    $admin = User::factory()->admin()->create();

    // Invalid numeric payload rejected, while minimal/blank fields succeed
    $this->actingAs($admin)
        ->postJson(route('equipment-breakdowns.store'), [
            'equipment_code' => '',
            'estimated_cost' => -100,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['estimated_cost']);

    $this->actingAs($admin)
        ->postJson(route('equipment-breakdowns.store'), [
            'notes' => 'سجل عطل سريع بدون حقول إلزامية',
        ])
        ->assertCreated();

    // Valid creation
    $this->actingAs($admin)
        ->postJson(route('equipment-breakdowns.store'), [
            'equipment_code' => 'EQ-NEW-808',
            'category' => BreakdownCategory::Electrical->value,
            'severity' => BreakdownSeverity::Medium->value,
            'breakdown_date' => today()->subDays(2)->format('Y-m-d'),
            'workshop_entry_date' => today()->subDay()->format('Y-m-d'),
            'expected_repair_date' => today()->addDays(3)->format('Y-m-d'),
            'repair_status' => RepairStatus::InWorkshop->value,
            'spare_parts_delay' => true,
            'estimated_cost' => 6400.50,
            'notes' => 'عطل كهربائي بالضفيرة',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'تمت الإضافة بنجاح');

    $breakdown = EquipmentBreakdown::query()->where('equipment_code', 'EQ-NEW-808')->firstOrFail();

    $csv = $this->actingAs($admin)
        ->get(route('equipment-breakdowns.export', ['search' => 'EQ-NEW-808']))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('EQ-NEW-808')
        ->and(AuditLog::query()->where('event', AuditEvent::Exported)->where('auditable_type', EquipmentBreakdown::class)->count())->toBe(1);

    $this->actingAs($admin)
        ->deleteJson(route('equipment-breakdowns.destroy', $breakdown))
        ->assertOk()
        ->assertJsonPath('message', 'تم الحذف بنجاح');

    expect($breakdown->fresh()->trashed())->toBeTrue();
});
