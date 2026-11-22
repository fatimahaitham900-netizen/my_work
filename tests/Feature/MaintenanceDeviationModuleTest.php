<?php

declare(strict_types=1);

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\MaintenanceDeviation;
use App\Models\User;
use App\Services\MaintenanceDeviationService;
use Database\Seeders\MaintenanceDeviationsRealDataSeeder;

it('seeds all 30 month days (17 filled + 13 open rows to end of month) and computes 99% / 68% / 1% / 32% averages accurately', function (): void {
    $this->seed(MaintenanceDeviationsRealDataSeeder::class);

    $summary = app(MaintenanceDeviationService::class)->summaryAverages();

    expect(MaintenanceDeviation::query()->count())->toBe(30)
        ->and($summary['active_days'])->toBe(11)
        ->and($summary['daily_maintenance_rate'])->toBe(99)
        ->and($summary['preventive_maintenance_rate'])->toBe(68)
        ->and($summary['daily_deviation_rate'])->toBe(1)
        ->and($summary['preventive_deviation_rate'])->toBe(32);
});

it('renders maintenance deviations page and dashboard box with regular and preventive deviation percentages', function (): void {
    $this->seed(MaintenanceDeviationsRealDataSeeder::class);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('انحراف الصيانات')
        ->assertSee('نسبة انحراف الصيانة العادية')
        ->assertSee('نسبة انحراف الصيانة الوقائية')
        ->assertSee('1%')
        ->assertSee('32%');

    $this->actingAs($admin)
        ->get(route('maintenance-deviations.index'))
        ->assertOk()
        ->assertSee('انحراف الصيانات')
        ->assertSee('مسؤول الصيانة مجاز')
        ->assertSee('99%')
        ->assertSee('68%')
        ->assertSee('32%');
});

it('creates, updates, exports csv, logs audits, and soft-deletes maintenance deviation records', function (): void {
    $this->seed(MaintenanceDeviationsRealDataSeeder::class);
    $admin = User::factory()->admin()->create();
    $viewer = User::factory()->executiveViewer()->create();

    $createResponse = $this->actingAs($admin)
        ->postJson(route('maintenance-deviations.store'), [
            'day_number' => 18,
            'row_type' => 'active',
            'daily_maintenance_rate' => 98,
            'preventive_maintenance_rate' => 80,
        ])
        ->assertCreated();

    $record = MaintenanceDeviation::query()->findOrFail($createResponse->json('data.id'));

    expect((int) round((float) $record->daily_deviation_rate))->toBe(2)
        ->and((int) round((float) $record->preventive_deviation_rate))->toBe(20);

    $this->actingAs($admin)
        ->putJson(route('maintenance-deviations.update', $record), [
            'day_number' => 18,
            'row_type' => 'active',
            'daily_maintenance_rate' => 99,
            'preventive_maintenance_rate' => 75,
        ])
        ->assertOk();

    expect((int) round((float) $record->fresh()->preventive_deviation_rate))->toBe(25)
        ->and($record->auditLogs()->where('event', AuditEvent::Updated)->count())->toBe(1);

    $csv = $this->actingAs($admin)
        ->get(route('maintenance-deviations.export'))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('مسؤول الصيانة مجاز')
        ->and(AuditLog::query()->where('event', AuditEvent::Exported)->where('auditable_type', MaintenanceDeviation::class)->count())->toBe(1);

    $this->actingAs($viewer)->deleteJson(route('maintenance-deviations.destroy', $record))->assertForbidden();
    $this->actingAs($admin)->deleteJson(route('maintenance-deviations.destroy', $record))->assertOk();

    expect($record->fresh()->trashed())->toBeTrue();
});
