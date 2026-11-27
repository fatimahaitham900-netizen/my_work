<?php

declare(strict_types=1);

use App\Models\Driver;
use App\Models\EquipmentDistribution;
use App\Models\User;
use App\Services\EquipmentDistributionService;
use Illuminate\Http\UploadedFile;

it('imports equipment distributions, renders exact columns, builds summary by type, filters, exports, and purges', function (): void {
    $admin = User::factory()->admin()->create();

    $csv = "\xEF\xBB\xBF"
        ."نوع المعدة,الفرع,الموقع,حالة المعدة,ملاحظه\n"
        ."حفارات,نجران,تشغيل نجران,متوقفة بدون عمل,بدون عمل\n"
        ."حفارات,المدينة,مشروع الدار,تم تشغيلها ساعة أو أكثر,بدون عمل فعلي\n"
        ."قلابات,نجران,الورشة المركزية بنجران,متعطلة في الورشة,تقييد الي للمعدة في الورشة\n"
        ."فرادة إسفلتية,أبها,مشروع أحد رفيدة,تم تشغيلها ساعة أو أكثر,0\n";

    $file = UploadedFile::fake()->createWithContent('distribution.csv', $csv);

    $this->actingAs($admin)
        ->post(route('equipment-distributions.import.direct'), ['file' => $file])
        ->assertRedirect(route('equipment-distributions.index'));

    expect(EquipmentDistribution::query()->count())->toBe(4);

    // 1. Table view shows exact imported columns in exact order & sidebar link
    $tableResponse = $this->actingAs($admin)
        ->get(route('equipment-distributions.index'))
        ->assertOk()
        ->assertSee('توزيعة المعدات')
        ->assertSee('ملخص حسب النوع')
        ->assertSee('حفارات')
        ->assertSee('الورشة المركزية بنجران')
        ->assertSee('تم تشغيلها ساعة أو أكثر');

    $html = $tableResponse->getContent();
    preg_match_all('/<th\b[^>]*>(.*?)<\/th>/su', $html, $matches);
    $headers = array_map(static fn (string $h): string => trim(strip_tags($h)), $matches[1]);
    expect($headers)->toBe(['نوع المعدة', 'الفرع', 'الموقع', 'حالة المعدة', 'ملاحظه']);

    // 2. Summary by Type view (?view=summary) renders ONLY branch breakdown per type with zeros hidden
    $this->actingAs($admin)
        ->get(route('equipment-distributions.index', ['view' => 'summary']))
        ->assertOk()
        ->assertDontSee('ملخص توزيعة المعدات حسب النوع والحالة')
        ->assertSee('ملخص توزيعة المعدات حسب النوع والفرع')
        ->assertSee('حفارات')
        ->assertSee('قلابات')
        ->assertSee('فرادة إسفلتية')
        ->assertSee('نجران');

    // 2.5. Summary by Site view (?view=site_summary) groups sites under each branch with branch subtotals and zeros hidden
    $this->actingAs($admin)
        ->get(route('equipment-distributions.index', ['view' => 'site_summary']))
        ->assertOk()
        ->assertSee('ملخص توزيعة المعدات حسب الفرع والموقع')
        ->assertSee('إجمالي فرع نجران')
        ->assertSee('تشغيل نجران')
        ->assertSee('مشروع الدار')
        ->assertSee('الورشة المركزية بنجران');

    // 3. Filter via partial XHR
    $this->actingAs($admin)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('equipment-distributions.index', ['equipment_type' => 'حفارات']))
        ->assertOk()
        ->assertJsonPath('total', 2);

    // 4. Create new record via JSON
    $this->actingAs($admin)
        ->postJson(route('equipment-distributions.store'), [
            'equipment_type' => 'شيولات',
            'branch' => 'مكة',
            'site_location' => 'مشروع ولي العهد',
            'equipment_status' => 'تم تشغيلها ساعة أو أكثر',
            'notes' => '0',
        ])
        ->assertCreated();

    expect(EquipmentDistribution::query()->count())->toBe(5);

    // 5. Export CSV
    $exported = extractExportText($this->actingAs($admin)
        ->get(route('equipment-distributions.export'))
        ->assertOk()
        ->streamedContent());

    expect($exported)->toContain('حفارات')
        ->and($exported)->toContain('شيولات');

    // 6. Purge all via POST
    $this->actingAs($admin)
        ->post(route('equipment-distributions.purge-all'))
        ->assertRedirect(route('equipment-distributions.index'));

    expect(EquipmentDistribution::query()->count())->toBe(0);
});

it('enforces rbac permissions on equipment distribution endpoints', function (): void {
    $viewer = User::factory()->executiveViewer()->create();
    $supervisor = User::factory()->fleetSupervisor()->create();
    $record = EquipmentDistribution::factory()->create();

    $this->actingAs($viewer)
        ->get(route('equipment-distributions.index'))
        ->assertOk();

    $this->actingAs($viewer)
        ->postJson(route('equipment-distributions.store'), ['equipment_type' => 'حفارات'])
        ->assertForbidden();

    $this->actingAs($supervisor)
        ->postJson(route('equipment-distributions.store'), ['equipment_type' => 'حفارات'])
        ->assertCreated();

    $this->actingAs($supervisor)
        ->deleteJson(route('equipment-distributions.destroy', $record))
        ->assertForbidden();
});

it('builds the combined equipment and drivers summary linked across distributions and drivers', function (): void {
    $admin = User::factory()->admin()->create();

    // Equipment distributions: 4 شيولات (2 operating, 1 no driver, 1 broken) + 2 قلابات (1 operating, 1 broken)
    EquipmentDistribution::factory()->create([
        'equipment_code' => 'SH_01',
        'equipment_type' => 'شيولات',
        'branch' => 'الرياض',
        'equipment_status' => 'تم تشغيلها ساعة أو أكثر',
    ]);
    EquipmentDistribution::factory()->create([
        'equipment_code' => 'SH_02',
        'equipment_type' => 'شيولات',
        'branch' => 'الرياض',
        'equipment_status' => 'تم تشغيلها ساعة أو أكثر',
    ]);
    EquipmentDistribution::factory()->create([
        'equipment_code' => 'SH_03',
        'equipment_type' => 'شيولات',
        'branch' => 'الرياض',
        'equipment_status' => 'متوقفة بدون سائق',
    ]);
    EquipmentDistribution::factory()->create([
        'equipment_code' => 'SH_04',
        'equipment_type' => 'شيولات',
        'branch' => 'الرياض',
        'equipment_status' => 'متعطلة في الورشة',
        'notes' => 'بدون سائق', // broken must NOT be counted in no_driver_count
    ]);
    EquipmentDistribution::factory()->create([
        'equipment_code' => 'DT_01',
        'equipment_type' => 'قلابات',
        'branch' => 'مكة',
        'equipment_status' => 'تم تشغيلها ساعة أو أكثر',
    ]);
    EquipmentDistribution::factory()->create([
        'equipment_code' => 'DT_02',
        'equipment_type' => 'قلابات',
        'branch' => 'مكة',
        'equipment_status' => 'متعطلة في الورشة',
    ]);
    // Generators & compressors with higher counts must still be sorted at the bottom of the table
    EquipmentDistribution::factory()->count(5)->create([
        'equipment_type' => 'مولدات كهرباء',
        'branch' => 'المدينة',
        'equipment_status' => 'تم تشغيلها ساعة أو أكثر',
    ]);
    EquipmentDistribution::factory()->count(3)->create([
        'equipment_type' => 'كمبروسرات',
        'branch' => 'نجران',
        'equipment_status' => 'تم تشغيلها ساعة أو أكثر',
    ]);

    // Drivers: 1 company + 1 external for شيولات, 1 company for قلابات (linked via equipment_code)
    Driver::factory()->create([
        'equipment_code' => 'SH_01',
        'branch' => 'الرياض',
        'sponsor' => 'كفاله شركة',
    ]);
    Driver::factory()->create([
        'equipment_code' => 'SH_02',
        'branch' => 'الرياض',
        'sponsor' => 'عمالة يومية',
    ]);
    Driver::factory()->create([
        'equipment_code' => 'DT_01',
        'branch' => 'مكة',
        'sponsor' => 'كفاله شركة',
    ]);

    $summary = app(EquipmentDistributionService::class)->summaryEquipmentAndDrivers();
    $rowsByType = collect($summary['rows'])->keyBy('type');
    $orderedTypes = array_column($summary['rows'], 'type');

    expect($orderedTypes)->toBe(['شيولات', 'قلابات', 'مولدات كهرباء', 'كمبروسرات'])
        ->and($rowsByType->get('شيولات'))->toMatchArray([
            'company_total' => 4,
            'company_drivers' => 1,
            'external_drivers' => 1,
            'no_driver_count' => 1,
            'broken_count' => 1,
        ])->and($rowsByType->get('قلابات'))->toMatchArray([
            'company_total' => 2,
            'company_drivers' => 1,
            'external_drivers' => 0,
            'no_driver_count' => 0,
            'broken_count' => 1,
        ])->and($summary['totals'])->toMatchArray([
            'company_total' => 14,
            'company_drivers' => 2,
            'external_drivers' => 1,
            'no_driver_count' => 1,
            'broken_count' => 2,
        ]);

    $this->actingAs($admin)
        ->get(route('equipment-distributions.index', ['view' => 'equipment_drivers']))
        ->assertOk()
        ->assertSee('إجمالي المعدات والسائقين')
        ->assertSee('إجمالي الشركة')
        ->assertSee('سائقين على كفالة الشركة')
        ->assertSee('سائقين عمالة خارجية')
        ->assertSee('معدات بدون سائق')
        ->assertSee('المعدات المتعطلة')
        ->assertSee('شيولات')
        ->assertSee('قلابات');
});
