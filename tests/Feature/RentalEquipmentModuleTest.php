<?php

declare(strict_types=1);

use App\Models\EquipmentBreakdown;
use App\Models\EquipmentDistribution;
use App\Models\IdleProjectEquipment;
use App\Models\RentalEquipment;
use App\Models\UnassignedDriver;
use App\Models\User;
use Illuminate\Http\UploadedFile;

it('lists, filters, creates, imports, purges rental equipment and syncs live count to dashboard card', function (): void {
    $admin = User::factory()->admin()->create();

    // 1. Direct CSV import matching the user's exact columns (including a footer SUM row that must be skipped)
    $csvContent = "\xEF\xBB\xBF"
        ."م,اسم المقاول,النوع,مبلغ الايجار,نوع الايجار,الفرع,المشروع\n"
        ."1,شركة ابن الجزيرة المحدودة,حفار,16000,شهري,الرياض,الحائر\n"
        ."2,شركة ابن الجزيرة المحدودة,حفار,16000,شهري,الرياض,الحائر\n"
        ."3,شركة ابن الجزيرة المحدودة,حفار,16000,شهري,الرياض,الحائر\n"
        .",,,48000,,,\n";

    $file = UploadedFile::fake()->createWithContent('rental.csv', $csvContent);

    $this->actingAs($admin)
        ->post(route('rental-equipment.import.direct'), ['file' => $file])
        ->assertRedirect(route('rental-equipment.index'));

    expect(RentalEquipment::query()->count())->toBe(3)
        ->and((float) RentalEquipment::query()->sum('rental_amount'))->toBe(48000.0);

    // 2. Page renders all columns and grand total 48,000
    $this->actingAs($admin)
        ->get(route('rental-equipment.index'))
        ->assertOk()
        ->assertSee('معدات الإيجار')
        ->assertSee('شركة ابن الجزيرة المحدودة')
        ->assertSee('حفار')
        ->assertSee('16,000')
        ->assertSee('48,000')
        ->assertSee('شهري')
        ->assertSee('الرياض')
        ->assertSee('الحائر');

    // 3. Dashboard card "إجمالي معدات الإيجار بالمشاريع" is linked to rental-equipment.index and shows live count (3) & amount (48,000)
    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('إجمالي معدات الإيجار بالمشاريع')
        ->assertSee('إجمالي معدات الإيجار')
        ->assertSee('إجمالي مبالغ الإيجار')
        ->assertSee(route('rental-equipment.index'))
        ->assertSeeInOrder(['إجمالي معدات الإيجار بالمشاريع', '3', '48,000']);

    // 4. Create new record via JSON and verify count increments to 4
    $this->actingAs($admin)
        ->postJson(route('rental-equipment.store'), [
            'contractor_name' => 'مؤسسة الخليج',
            'equipment_type' => 'شيول',
            'rental_amount' => 12000,
            'rental_type' => 'شهري',
            'branch' => 'نجران',
            'project' => 'أحد رفيدة',
        ])
        ->assertCreated();

    expect(RentalEquipment::query()->count())->toBe(4);

    // 5. Filter via partial XHR
    $this->actingAs($admin)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('rental-equipment.index', ['branch' => 'نجران']))
        ->assertOk()
        ->assertJsonPath('total', 1);

    // 6. Export CSV
    $exported = $this->actingAs($admin)
        ->get(route('rental-equipment.export'))
        ->assertOk()
        ->streamedContent();

    expect($exported)->toContain('شركة ابن الجزيرة المحدودة')
        ->and($exported)->toContain('مؤسسة الخليج');

    // 7. Purge all records and verify count returns to 0
    $this->actingAs($admin)
        ->delete(route('rental-equipment.purge-all'))
        ->assertRedirect(route('rental-equipment.index'));

    expect(RentalEquipment::query()->count())->toBe(0);
});

it('enforces rbac permissions on rental equipment endpoints', function (): void {
    $viewer = User::factory()->executiveViewer()->create();
    $supervisor = User::factory()->fleetSupervisor()->create();
    $record = RentalEquipment::factory()->create();

    $this->actingAs($viewer)
        ->get(route('rental-equipment.index'))
        ->assertOk();

    $this->actingAs($viewer)
        ->postJson(route('rental-equipment.store'), ['contractor_name' => 'Test'])
        ->assertForbidden();

    $this->actingAs($supervisor)
        ->postJson(route('rental-equipment.store'), ['contractor_name' => 'Allowed'])
        ->assertCreated();

    $this->actingAs($supervisor)
        ->deleteJson(route('rental-equipment.destroy', $record))
        ->assertForbidden();
});

it('analyzes rental equipment replacement alternatives from equipment distributions', function (): void {
    $admin = User::factory()->admin()->create();

    // 2 rented excavators (حفار) at 15,000 each = 30,000
    RentalEquipment::factory()->create([
        'equipment_type' => 'حفار',
        'rental_amount' => 15000,
        'branch' => 'الرياض',
    ]);
    RentalEquipment::factory()->create([
        'equipment_type' => 'حفار',
        'rental_amount' => 15000,
        'branch' => 'الرياض',
    ]);

    // Matching company-owned equipment in توزيعة المعدات (plural "حفارات" & synonym "بوكلينات")
    EquipmentDistribution::factory()->create([
        'equipment_code' => 'EX_000101',
        'equipment_type' => 'حفارات',
        'branch' => 'الرياض',
        'site_location' => 'مشروع الجنادرية',
        'equipment_status' => 'متوقفة بدون عمل',
    ]);
    EquipmentDistribution::factory()->create([
        'equipment_code' => 'EX_000102',
        'equipment_type' => 'بوكلينات',
        'branch' => 'نجران',
        'site_location' => 'الورشة المركزية بنجران',
        'equipment_status' => 'متعطلة في الورشة',
    ]);
    EquipmentDistribution::factory()->create([
        'equipment_code' => 'EX_000103',
        'equipment_type' => 'حفارات',
        'branch' => 'مكة',
        'site_location' => 'مشروع ولي العهد',
        'equipment_status' => 'متوقفة بدون سائق',
    ]);
    // Operating equipment with notes "بدون عمل فعلي" MUST also be detected as idle_no_work!
    EquipmentDistribution::factory()->create([
        'equipment_code' => 'EX_000105',
        'equipment_type' => 'حفارات',
        'branch' => 'الرياض',
        'site_location' => 'مشروع الحائر',
        'equipment_status' => 'تم تشغيلها ساعة أو أكثر',
        'notes' => 'بدون عمل فعلي',
    ]);
    // Matching idle project equipment from معدات بدون عمل فعلي بالمشاريع
    IdleProjectEquipment::factory()->create([
        'equipment_code' => 'EX_000106',
        'equipment_type' => 'بوكلينات',
        'branch' => 'الرياض',
        'site_location' => 'مشروع الحائر',
        'equipment_status' => 'بدون عمل فعلي',
        'total_idle_days' => 9,
    ]);
    // Operating equipment with normal notes should NOT be counted as an idle/broken alternative
    EquipmentDistribution::factory()->create([
        'equipment_code' => 'EX_000104',
        'equipment_type' => 'حفارات',
        'branch' => 'الرياض',
        'site_location' => 'مشروع الدار',
        'equipment_status' => 'تم تشغيلها ساعة أو أكثر',
        'notes' => '0',
    ]);

    // Matching breakdown record for EX_000102 so fault, breakdown_date, and downtime_days are pulled automatically
    $breakdownDate = today()->subDays(12)->format('Y-m-d');
    EquipmentBreakdown::factory()->create([
        'equipment_code' => 'EX_000102',
        'fault_description' => 'عطل طلمبة هيدروليك رئيسية',
        'breakdown_date' => $breakdownDate,
        'workshop_status' => 'توريد قطع غيار',
    ]);

    // Matching unassigned driver for excavator (سائق بوكلين)
    UnassignedDriver::factory()->create([
        'driver_name' => 'سالم الحربي',
        'employee_id' => '8844',
        'job_title' => 'سائق بوكلين',
        'location' => 'ورشة نجران',
    ]);

    $this->actingAs($admin)
        ->get(route('rental-equipment.index', ['view' => 'analysis']))
        ->assertOk()
        ->assertSee('تحليل بدائل الإيجار')
        ->assertSee('حفار')
        ->assertSee('يمكن الإحلال فوراً (معدات متوقفة بدون عمل)')
        ->assertSee('30,000')
        ->assertSee('EX_000101')
        ->assertSee('EX_000102')
        ->assertSee('عطل طلمبة هيدروليك رئيسية')
        ->assertSee($breakdownDate)
        ->assertSee('12 يوم')
        ->assertSee('EX_000103')
        ->assertSee('EX_000105')
        ->assertSee('EX_000106')
        ->assertSee('سالم الحربي')
        ->assertSee('سائق بوكلين')
        ->assertDontSee('EX_000104');
});
