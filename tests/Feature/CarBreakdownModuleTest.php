<?php

declare(strict_types=1);

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\CarBreakdown;
use App\Models\User;
use Database\Seeders\CarBreakdownsRealDataSeeder;

it('lists, searches, and multi-filters car breakdowns', function (): void {
    $user = User::factory()->operationsManager()->create();

    CarBreakdown::factory()->create([
        'car_code' => 'C_000287',
        'car_type' => 'ايسوزو 2018 بكب غمارتين',
        'plate_number' => 'ب د هـ 3008',
        'model_year' => '2018',
        'department' => 'التنفيذ',
        'fuel_type' => 'ديزل',
        'branch' => 'البحر الاحمر',
        'condition_status' => 'بخاخات + طلمبة + تربو بالمدينة',
    ]);

    CarBreakdown::factory()->create([
        'car_code' => 'C_000294',
        'car_type' => 'جمس 2009 اكيديا',
        'plate_number' => 'ا ل ه 4504',
        'model_year' => '2009',
        'department' => 'القانونية',
        'fuel_type' => 'بنزين',
        'branch' => 'الرياض',
        'condition_status' => 'فحص شامل',
    ]);

    $this->actingAs($user)
        ->get(route('car-breakdowns.index'))
        ->assertOk()
        ->assertSee('السيارات المتعطلة بالورش')
        ->assertSee('C_000287')
        ->assertSee('C_000294');

    $this->actingAs($user)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('car-breakdowns.index', ['branch' => 'البحر الاحمر', 'fuel_type' => 'ديزل']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('kpis.major_mechanical', 1)
        ->assertJsonPath('kpis.needs_inspection', 1);
});

it('creates with optional/blank fields, updates, logs audit events, exports csv, and soft deletes', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson(route('car-breakdowns.store'), [
            'branch' => 'نجران',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'تمت الإضافة بنجاح');

    $createResponse = $this->actingAs($admin)
        ->postJson(route('car-breakdowns.store'), [
            'car_code' => 'C_000999',
            'car_type' => 'تويوتا 2020 هايلكس',
            'plate_number' => 'أ ب ج 1234',
            'model_year' => '2020',
            'previous_receiver' => 'علي قاسم',
            'employee_id' => '1544',
            'department' => 'المشتريات',
            'job_title' => 'مندوب مشتريات',
            'fuel_type' => 'ديزل',
            'branch' => 'نجران',
            'condition_status' => 'ماكينة',
        ])
        ->assertCreated();

    $car = CarBreakdown::query()->findOrFail($createResponse->json('data.id'));

    $this->actingAs($admin)
        ->getJson(route('car-breakdowns.show', $car))
        ->assertOk()
        ->assertJsonPath('can_view_audit_logs', true)
        ->assertJsonPath('record.car_code', 'C_000999');

    $this->actingAs($admin)
        ->putJson(route('car-breakdowns.update', $car), [
            'car_code' => 'C_000999',
            'condition_status' => 'تم الإصلاح وجاهزة للتسليم',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'تم التحديث بنجاح');

    expect($car->fresh()->condition_status)->toBe('تم الإصلاح وجاهزة للتسليم')
        ->and($car->auditLogs()->where('event', AuditEvent::Updated)->count())->toBe(1);

    $csv = $this->actingAs($admin)
        ->get(route('car-breakdowns.export', ['search' => 'C_000999']))
        ->assertOk()
        ->streamedContent();

    expect(extractExportText($csv))->toContain('C_000999')
        ->and(AuditLog::query()->where('event', AuditEvent::Exported)->where('auditable_type', CarBreakdown::class)->count())->toBe(1);

    $this->actingAs($admin)
        ->deleteJson(route('car-breakdowns.destroy', $car))
        ->assertOk()
        ->assertJsonPath('message', 'تم الحذف بنجاح');

    expect($car->fresh()->trashed())->toBeTrue();
});

it('enforces rbac permissions on car breakdowns endpoints', function (): void {
    $viewer = User::factory()->executiveViewer()->create();
    $supervisor = User::factory()->fleetSupervisor()->create();
    $car = CarBreakdown::factory()->create();

    $this->actingAs($viewer)
        ->get(route('car-breakdowns.index'))
        ->assertOk();

    $this->actingAs($viewer)
        ->postJson(route('car-breakdowns.store'), ['car_code' => 'C_000500'])
        ->assertForbidden();

    $this->actingAs($supervisor)
        ->postJson(route('car-breakdowns.store'), ['car_code' => 'C_000501'])
        ->assertCreated();

    $this->actingAs($supervisor)
        ->deleteJson(route('car-breakdowns.destroy', $car))
        ->assertForbidden();
});

it('seeds all 14 real car breakdown records accurately', function (): void {
    $this->seed(CarBreakdownsRealDataSeeder::class);

    expect(CarBreakdown::query()->count())->toBe(14)
        ->and(CarBreakdown::query()->where('car_code', 'C_000205')->exists())->toBeTrue()
        ->and(CarBreakdown::query()->where('car_code', 'C_000313')->exists())->toBeTrue()
        ->and(CarBreakdown::query()->where('fuel_type', 'ديزل')->count())->toBe(2);
});

it('classifies cars vs buses and displays separate dashboard cards for cars and buses', function (): void {
    $admin = User::factory()->admin()->create();

    CarBreakdown::factory()->create([
        'car_code' => 'C_000205',
        'vehicle_category' => CarBreakdown::CATEGORY_CAR,
        'branch' => 'نجران',
    ]);

    $this->actingAs($admin)
        ->postJson(route('car-breakdowns.store'), [
            'car_code' => 'BUS_000013',
            'car_type' => 'باص',
            'branch' => 'نجران',
            'fuel_type' => 'ديزل',
            'condition_status' => 'متعطل بالورشة',
        ])
        ->assertCreated()
        ->assertJsonPath('data.vehicle_category', 'باص');

    $this->actingAs($admin)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('car-breakdowns.index', ['vehicle_category' => 'باص']))
        ->assertOk()
        ->assertJsonPath('total', 1);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('موقف الأعطال بالورش')
        ->assertSee('إجمالي السيارات المتعطلة')
        ->assertSee('إجمالي الباصات المتعطلة');
});
