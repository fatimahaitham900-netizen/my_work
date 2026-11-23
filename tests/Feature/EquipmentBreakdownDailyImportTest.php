<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BreakdownCategory;
use App\Enums\BreakdownSeverity;
use App\Enums\RepairStatus;
use App\Models\EquipmentBreakdown;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

it('downloads the official daily import csv template with standard headers', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('equipment-breakdowns.import.template'));

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertHeader('content-disposition', 'attachment; filename="equipment-breakdowns-daily-template.csv"');

    $content = $response->streamedContent();
    expect($content)->toContain('الكود')
        ->and($content)->toContain('المعدة')
        ->and($content)->toContain('الورشة')
        ->and($content)->toContain('تاريخ التوقف')
        ->and($content)->toContain('RD_000001');
});

it('rejects invalid or non-spreadsheet file uploads with proper validation error messages', function (): void {
    $admin = User::factory()->admin()->create();

    $invalidFile = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    $response = $this->actingAs($admin)->post(route('equipment-breakdowns.import.preview'), [
        'file' => $invalidFile,
    ]);

    $response->assertSessionHasErrors('file');
});

it('previews daily updates without modifying the database and accurately detects new, changed, and unchanged rows with zero N+1 queries', function (): void {
    $admin = User::factory()->admin()->create();

    // 1. Existing record that will be modified
    $existingModified = EquipmentBreakdown::factory()->create([
        'equipment_code' => 'EQ-MOD-101',
        'equipment_name' => 'بلدوزر',
        'brand' => 'CAT',
        'workshop_name' => 'ورشه نجران',
        'breakdown_date' => '2026-05-10',
        'severity' => BreakdownSeverity::Medium,
        'fault_description' => 'عطل هيدروليك قديم',
        'workshop_status' => 'جاري الكشف',
        'estimated_cost' => 5000.00,
        'cost_note' => null,
        'notes' => 'ملاحظات سابقة',
    ]);

    // 2. Existing record that will remain unchanged
    $existingUnchanged = EquipmentBreakdown::factory()->create([
        'equipment_code' => 'EQ-SAME-202',
        'equipment_name' => 'كرين',
        'brand' => 'TADANO',
        'workshop_name' => 'ورشه الرياض',
        'breakdown_date' => '2026-06-01',
        'severity' => BreakdownSeverity::Critical,
        'fault_description' => 'كسر في الذراع الرئيسي',
        'workshop_status' => 'توريد قطع غيار',
        'estimated_cost' => 45000.00,
        'cost_note' => null,
        'notes' => 'بانتظار وصول القطع من دبي',
    ]);

    $initialCount = EquipmentBreakdown::query()->count();

    // Prepare CSV with 3 rows: 1 new, 1 changed, 1 unchanged
    $csvContent = implode("\n", [
        'م,المعدة,النوع,الكود,الورشة,تاريخ التوقف,حجم العطل,العطل,الموقف,المبلغ,تاريخ الإصلاح,ملاحظات الموقف',
        '1,بلدوزر معدل,CAT,EQ-MOD-101,ورشه نجران الجديدة,2026-05-10,ثقيل,عطل هيدروليك ومحرك جديد,دعم مالي,18000,,تم تحديث الموقف المالي',
        '2,كرين,TADANO,EQ-SAME-202,ورشه الرياض,2026-06-01,ثقيل,كسر في الذراع الرئيسي,توريد قطع غيار,45000,,بانتظار وصول القطع من دبي',
        '3,شنيور جديد,SANDVIK,EQ-NEW-303,ورشه المدينة,2026-06-15,خفيف,صيانة دورية لحساسات الشفط,قيد الاصلاح,3200,,سجل جديد تم إدخاله اليوم',
    ]);

    $file = UploadedFile::fake()->createWithContent('daily-update.csv', $csvContent);

    // Track DB queries to confirm ZERO N+1 queries during preview calculation
    DB::enableQueryLog();

    $response = $this->actingAs($admin)->post(route('equipment-breakdowns.import.preview'), [
        'file' => $file,
    ]);

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Verify DB count did not change!
    expect(EquipmentBreakdown::query()->count())->toBe($initialCount)
        ->and($existingModified->fresh()->workshop_name)->toBe('ورشه نجران');

    // Filter queries targeting equipment_breakdowns
    $breakdownSelectQueries = array_filter($queries, static function (array $q): bool {
        $sql = strtolower($q['query']);

        return str_contains($sql, 'equipment_breakdowns') && str_starts_with($sql, 'select');
    });

    // Exactly one select query with `WHERE equipment_code IN (...)`
    expect(count($breakdownSelectQueries))->toBeLessThanOrEqual(2);

    $response->assertRedirect(route('equipment-breakdowns.index'))
        ->assertSessionHas('status')
        ->assertSessionHas('equipment_breakdown_import_preview');

    $preview = session('equipment_breakdown_import_preview');
    expect($preview['total_count'])->toBe(3)
        ->and($preview['new_count'])->toBe(1)
        ->and($preview['changed_count'])->toBe(1)
        ->and($preview['unchanged_count'])->toBe(1);

    // Inspect row diffs
    $changedRow = collect($preview['rows'])->firstWhere('equipment_code', 'EQ-MOD-101');
    expect($changedRow['status'])->toBe('changed')
        ->and($changedRow['diffs'])->toHaveKeys(['equipment_name', 'workshop_name', 'severity', 'fault_description', 'workshop_status', 'cost', 'notes']);

    $newRow = collect($preview['rows'])->firstWhere('equipment_code', 'EQ-NEW-303');
    expect($newRow['status'])->toBe('new');

    $sameRow = collect($preview['rows'])->firstWhere('equipment_code', 'EQ-SAME-202');
    expect($sameRow['status'])->toBe('unchanged')
        ->and($sameRow['diffs'])->toBeEmpty();

    // When visiting the page, the preview banner and comparison table are rendered
    $viewResponse = $this->actingAs($admin)->get(route('equipment-breakdowns.index'));

    $viewResponse->assertOk()
        ->assertSee('معاينة ملف التحديث اليومي لمعدات الورش')
        ->assertSee('EQ-NEW-303')
        ->assertSee('EQ-MOD-101')
        ->assertSee('تطبيق التحديثات (اعتماد نهائي)')
        ->assertSee('إلغاء المعاينة');
});

it('applies previewed daily updates atomically to the database, purges obsolete records, and clears session preview', function (): void {
    $admin = User::factory()->admin()->create();

    $existing = EquipmentBreakdown::factory()->create([
        'equipment_code' => 'EQ-APP-001',
        'equipment_name' => 'مولد قديم',
        'workshop_status' => 'جاري الكشف',
    ]);

    // An obsolete record that is NOT in the new daily update file (must be completely deleted)
    $obsolete = EquipmentBreakdown::factory()->create([
        'equipment_code' => 'EQ-OBSOLETE-999',
        'equipment_name' => 'معدة قديمة خرجت من الورشة',
    ]);

    $previewPayload = [
        'filename' => 'update-approved.csv',
        'uploaded_at' => '2026-06-20 10:00',
        'total_count' => 2,
        'new_count' => 1,
        'changed_count' => 1,
        'unchanged_count' => 0,
        'rows' => [
            [
                'index' => 1,
                'status' => 'changed',
                'equipment_code' => 'EQ-APP-001',
                'equipment_name' => 'مولد مجدد بالكامل',
                'raw_data' => [
                    'equipment_code' => 'EQ-APP-001',
                    'equipment_name' => 'مولد مجدد بالكامل',
                    'brand' => 'CAT',
                    'workshop_name' => 'ورشه الرياض',
                    'breakdown_date' => '2026-06-10',
                    'severity' => BreakdownSeverity::Critical->value,
                    'fault_description' => 'تم استبدال طلمبة الديزل',
                    'workshop_status' => 'تم الإصلاح',
                    'repair_status' => RepairStatus::Repaired->value,
                    'spare_parts_delay' => false,
                    'estimated_cost' => '15000.00',
                    'cost_note' => null,
                    'expected_repair_date' => '2026-06-20',
                    'notes' => 'جاهز للاستلام والتسليم للمشروع',
                    'category' => BreakdownCategory::Mechanical->value,
                ],
            ],
            [
                'index' => 2,
                'status' => 'new',
                'equipment_code' => 'EQ-NEW-888',
                'equipment_name' => 'رافعة تلسكوبية',
                'raw_data' => [
                    'equipment_code' => 'EQ-NEW-888',
                    'equipment_name' => 'رافعة تلسكوبية',
                    'brand' => 'JCB',
                    'workshop_name' => 'ورشه نجران',
                    'breakdown_date' => '2026-06-18',
                    'severity' => BreakdownSeverity::Medium->value,
                    'fault_description' => 'عطل في حساسات الذراع',
                    'workshop_status' => 'جاري الكشف',
                    'repair_status' => RepairStatus::InWorkshop->value,
                    'spare_parts_delay' => false,
                    'estimated_cost' => '7500.00',
                    'cost_note' => null,
                    'expected_repair_date' => '2026-06-25',
                    'notes' => 'وصلت الورشة صباح اليوم',
                    'category' => BreakdownCategory::Electrical->value,
                ],
            ],
        ],
    ];

    $response = $this->actingAs($admin)
        ->withSession(['equipment_breakdown_import_preview' => $previewPayload])
        ->post(route('equipment-breakdowns.import.apply'));

    $response->assertRedirect(route('equipment-breakdowns.index'))
        ->assertSessionHas('status')
        ->assertSessionMissing('equipment_breakdown_import_preview');

    // Verify database has both records updated & created!
    expect($existing->fresh()->equipment_name)->toBe('مولد مجدد بالكامل')
        ->and($existing->fresh()->workshop_status)->toBe('تم الإصلاح')
        ->and($existing->fresh()->repair_status)->toBe(RepairStatus::Repaired);

    $created = EquipmentBreakdown::query()->where('equipment_code', 'EQ-NEW-888')->firstOrFail();
    expect($created->equipment_name)->toBe('رافعة تلسكوبية')
        ->and($created->brand)->toBe('JCB');

    // Verify obsolete record was completely removed from active database!
    expect(EquipmentBreakdown::query()->where('equipment_code', 'EQ-OBSOLETE-999')->exists())->toBeFalse()
        ->and(EquipmentBreakdown::withTrashed()->where('equipment_code', 'EQ-OBSOLETE-999')->first()->trashed())->toBeTrue();
});

it('cancels preview without changing any database records', function (): void {
    $admin = User::factory()->admin()->create();

    $initialCount = EquipmentBreakdown::query()->count();

    $previewPayload = [
        'filename' => 'update-cancel.csv',
        'uploaded_at' => '2026-06-20 10:00',
        'total_count' => 1,
        'new_count' => 1,
        'changed_count' => 0,
        'unchanged_count' => 0,
        'rows' => [
            [
                'index' => 1,
                'status' => 'new',
                'equipment_code' => 'EQ-SHOULD-NOT-EXIST',
                'raw_data' => [
                    'equipment_code' => 'EQ-SHOULD-NOT-EXIST',
                    'equipment_name' => 'معدة ملغية',
                ],
            ],
        ],
    ];

    $response = $this->actingAs($admin)
        ->withSession(['equipment_breakdown_import_preview' => $previewPayload])
        ->post(route('equipment-breakdowns.import.cancel'));

    $response->assertRedirect(route('equipment-breakdowns.index'))
        ->assertSessionHas('status')
        ->assertSessionMissing('equipment_breakdown_import_preview');

    expect(EquipmentBreakdown::query()->count())->toBe($initialCount)
        ->and(EquipmentBreakdown::query()->where('equipment_code', 'EQ-SHOULD-NOT-EXIST')->exists())->toBeFalse();
});

it('imports and drops daily file directly onto the table, updating new records and purging old records in one step', function (): void {
    $admin = User::factory()->admin()->create();

    $existing = EquipmentBreakdown::factory()->create([
        'equipment_code' => 'EQ-DIR-001',
        'equipment_name' => 'بلدوزر قديم',
        'workshop_name' => 'ورشه نجران',
        'workshop_status' => 'تحت الفحص',
    ]);

    $obsolete = EquipmentBreakdown::factory()->create([
        'equipment_code' => 'EQ-DIR-OBSOLETE',
        'equipment_name' => 'شاحنة ستحذف',
    ]);

    $csvContent = implode("\n", [
        'الكود,المعدة,الماركة,الورشة,تاريخ التوقف,الشدة,وصف العطل,الموقف,التكلفة المقدرة,ملاحظات التكلفة,التاريخ المتوقع للخروج,ملاحظات الموقف',
        'EQ-DIR-001,بلدوزر محدث بالكامل,CAT,ورشه نجران,2026-06-01,عالي,تم الاصلاح بالكامل,جاهز للعمل,4500,,2026-06-05,ملاحظة 1',
        'EQ-DIR-NEW,حفار جديد وارد,كوماتسو,ورشه الرياض,2026-06-02,متوسط,عطل هيدروليك,في انتظار القطع,12000,,2026-06-10,ملاحظة 2',
    ]);

    $file = UploadedFile::fake()->createWithContent('daily-direct-import.csv', $csvContent);

    $response = $this->actingAs($admin)
        ->post(route('equipment-breakdowns.import.direct'), [
            'file' => $file,
        ]);

    $response->assertRedirect(route('equipment-breakdowns.index'))
        ->assertSessionHas('status')
        ->assertSessionMissing('equipment_breakdown_import_preview');

    // Verify existing updated
    expect($existing->fresh()->equipment_name)->toBe('بلدوزر محدث بالكامل')
        ->and($existing->fresh()->workshop_status)->toBe('جاهز للعمل');

    // Verify new created
    $created = EquipmentBreakdown::query()->where('equipment_code', 'EQ-DIR-NEW')->firstOrFail();
    expect($created->equipment_name)->toBe('حفار جديد وارد')
        ->and($created->brand)->toBe('كوماتسو');

    // Verify obsolete deleted
    expect(EquipmentBreakdown::query()->where('equipment_code', 'EQ-DIR-OBSOLETE')->exists())->toBeFalse()
        ->and(EquipmentBreakdown::withTrashed()->where('equipment_code', 'EQ-DIR-OBSOLETE')->first()->trashed())->toBeTrue();
});

it('gracefully imports spreadsheets with metadata or title rows above the actual table headers', function (): void {
    $admin = User::factory()->admin()->create();

    $csvWithTitle = implode("\n", [
        'تقرير الأعطال والمعدات المتعطلة بالورشة,,,,,,,,,,,',
        ',,,,,,,,,,,',
        'م,الكود,المعدة,النوع,الورشة,تاريخ التوقف,الشدة,العطل,الموقف,المبلغ,تاريخ الإصلاح,ملاحظات الموقف',
        '1,EQ-TITLE-01,شنيور حفر,Sandvik,ورشه نجران,2026-06-01,ثقيل,عطل هيدروليك,تحت الكشف,15000,2026-06-15,تم الكشف',
    ]);

    $file = UploadedFile::fake()->createWithContent('with-title.csv', $csvWithTitle);

    $response = $this->actingAs($admin)
        ->post(route('equipment-breakdowns.import.direct'), [
            'file' => $file,
        ]);

    $response->assertRedirect(route('equipment-breakdowns.index'))
        ->assertSessionHas('status');

    $record = EquipmentBreakdown::query()->where('equipment_code', 'EQ-TITLE-01')->firstOrFail();
    expect($record->equipment_name)->toBe('شنيور حفر')
        ->and($record->workshop_name)->toBe('ورشه نجران');
});

it('gracefully imports files without an explicit code column by generating deterministic codes', function (): void {
    $admin = User::factory()->admin()->create();

    $csvWithoutCodeHeader = implode("\n", [
        'المعدة,الورشة,الموقف,العطل',
        'بلدوزر عملاق,ورشه المدينة,جاهز للعمل,صيانة دورية',
    ]);

    $file = UploadedFile::fake()->createWithContent('no-code.csv', $csvWithoutCodeHeader);

    $response = $this->actingAs($admin)
        ->post(route('equipment-breakdowns.import.direct'), [
            'file' => $file,
        ]);

    $response->assertRedirect(route('equipment-breakdowns.index'))
        ->assertSessionHas('status');

    expect(EquipmentBreakdown::query()->where('equipment_name', 'بلدوزر عملاق')->exists())->toBeTrue();
});

it('handles enterprise html tables saved with xls extension', function (): void {
    $admin = User::factory()->admin()->create();

    $htmlContent = '<html><body><table>'.
        '<tr><th>الكود</th><th>المعدة</th><th>الورشة</th><th>الموقف</th></tr>'.
        '<tr><td>EQ-HTML-99</td><td>رافعة برجية</td><td>ورشه الرياض</td><td>جاري العمل</td></tr>'.
        '</table></body></html>';

    $file = UploadedFile::fake()->createWithContent('export.xls', $htmlContent);

    $response = $this->actingAs($admin)
        ->post(route('equipment-breakdowns.import.direct'), [
            'file' => $file,
        ]);

    $response->assertRedirect(route('equipment-breakdowns.index'))
        ->assertSessionHas('status');

    $record = EquipmentBreakdown::query()->where('equipment_code', 'EQ-HTML-99')->firstOrFail();
    expect($record->equipment_name)->toBe('رافعة برجية');
});

it('purges all equipment breakdown records via the purge-all button endpoint', function (): void {
    $admin = User::factory()->admin()->create();

    EquipmentBreakdown::factory()->count(5)->create();
    expect(EquipmentBreakdown::query()->count())->toBeGreaterThanOrEqual(5);

    $response = $this->actingAs($admin)
        ->withSession(['equipment_breakdown_import_preview' => ['total_count' => 1]])
        ->delete(route('equipment-breakdowns.purge-all'));

    $response->assertRedirect(route('equipment-breakdowns.index'))
        ->assertSessionHas('status')
        ->assertSessionMissing('equipment_breakdown_import_preview');

    expect(EquipmentBreakdown::query()->count())->toBe(0);
});

it('imports csv with quoted commas, text repair dates, text cost notes, and clears stale notes when new file cells are empty', function (): void {
    $admin = User::factory()->admin()->create();

    $existing = EquipmentBreakdown::factory()->create([
        'equipment_code' => 'WL_000022',
        'equipment_name' => 'شيول قديم',
        'notes' => 'ملاحظة قديمة يجب مسحها',
        'expected_repair_date' => '2026-01-01',
    ]);

    $csvContent = implode("\n", [
        'م,المعده,النوع,الكود,الورشه,تاريخ التوقف ,ايام التوقف,تاريخ اليوم ,حجم العطل,العطل,الموقف,المبلغ,تاريخ الإصلاح ,ملاحظات الموقف ',
        '64,شيول,CAT,WL_000022,ورشه نجران,2026-11-15,8,2026-11-23,خفيف,"ماكينة ماسكة ,, جاري الكشف",جاري الكشف,,22-11-2026,',
        '54,بلدوزر,CAT,BU_000028,ورشه مكة المكرمة,2026-11-08,15,2026-11-23,خفيف,رولات عدد5 مع المسامير والكابات,توريد قطع,,من الاعتماد البنكي ,',
        '75,تريلاصندوق,VOLVO,TR_000074,ورشه المدينة ,2026-11-19,4,2026-11-23,خفيف,كمبيوتر rpm+حساس التربو,قيد الاصلاح,خارج الورشه,23-11-2026,',
    ]);

    $file = UploadedFile::fake()->createWithContent('exact-breakdowns.csv', $csvContent);

    $response = $this->actingAs($admin)->post(route('equipment-breakdowns.import.direct'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('equipment-breakdowns.index'))
        ->assertSessionHas('status');

    $wl = EquipmentBreakdown::query()->where('equipment_code', 'WL_000022')->firstOrFail();
    expect($wl->fault_description)->toBe('ماكينة ماسكة ,, جاري الكشف')
        ->and($wl->expected_repair_date_display)->toBe('22/11/2026')
        ->and($wl->notes)->toBeNull();

    $bu = EquipmentBreakdown::query()->where('equipment_code', 'BU_000028')->firstOrFail();
    expect($bu->expected_repair_date_display)->toBe('من الاعتماد البنكي');

    $tr = EquipmentBreakdown::query()->where('equipment_code', 'TR_000074')->firstOrFail();
    expect($tr->cost_note)->toBe('خارج الورشه')
        ->and($tr->expected_repair_date_display)->toBe('23/11/2026');
});
