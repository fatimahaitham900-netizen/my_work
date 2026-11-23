<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BreakdownDriverStoppage;
use App\Models\CarBreakdown;
use App\Models\EquipmentBreakdown;
use App\Models\FinancialSupportTransfer;
use App\Models\IdleProjectEquipment;
use App\Models\MaintenanceDeviation;
use App\Models\OwnershipTransfer;
use App\Models\RegulatoryDocument;
use App\Models\SparePartTransfer;
use App\Models\TransportViolation;
use App\Models\UnassignedDriver;
use App\Models\User;
use Illuminate\Http\UploadedFile;

dataset('sidebar_importable_modules', [
    'ownership-transfers' => [
        'ownership-transfers',
        OwnershipTransfer::class,
        "م,كود المعدة,البيان,الفرع,رقم الهيكل,الحالة,ملاحظات\n1,OT-901,قلاب مان,نجران,CH-901,متأخر,بانتظار الجمارك",
        'equipment_code',
        'OT-901',
    ],
    'breakdown-stoppages' => [
        'breakdown-stoppages',
        BreakdownDriverStoppage::class,
        "م,اسم السائق,رقم وظيفي,المهنة,الفرع,كود المعدة,نوع المعدة,آخر تشغيل,تشخيص العطل,موقف الورش,الإجراء المتخذ\n1,أحمد العتيبي,4411,سائق قلاب,نجران,DT-901,قلاب,2026-11-10,عطل محرك,قيد الإصلاح,متابعة الورشة",
        'equipment_code',
        'DT-901',
    ],
    'unassigned-drivers' => [
        'unassigned-drivers',
        UnassignedDriver::class,
        "م,اسم السائق,المهنة,الرقم الوظيفي,الموقع,ملاحظات,تاريخ التوقف\n1,سالم الدوسري,سائق شيول,5522,ورشه نجران,جاهز للعمل,2026-11-05",
        'employee_id',
        '5522',
    ],
    'regulatory-documents' => [
        'regulatory-documents',
        RegulatoryDocument::class,
        "م,كود المعدة,نوع المعدة,رقم اللوحة,نوع النقل,الفرع الحالي,الرقم التسلسلي,الهجري,الميلادي,الموقف من التجديد\n1,RD-901,قلاب,أ ب ج 901,نقل عام,نجران,11223344,1447/06/01,2026-10-01,مطلوب تجديد",
        'entity_identifier',
        'RD-901',
    ],
    'transport-violations' => [
        'transport-violations',
        TransportViolation::class,
        "م,رقم المخالفة,السجل,التاريخ,المبلغ,الإدارة,مدينة المخالفة,شرح المخالفة,حالة الاعتراض,رقم اللوحة,الكود,السائق,الرقم الوظيفي,حالة الموظف,التحميل,موقف التصريح\n1,VIO-9911,الشركة,2026-11-12,5000,النقل,الرياض,حمولة زائدة,انتظار الرد,د هـ 123,TR-901,محمد,7711,متواجد,على السائق,يوجد تصريح",
        'violation_number',
        'VIO-9911',
    ],
    'spare-part-transfers' => [
        'spare-part-transfers',
        SparePartTransfer::class,
        "م,البند,المعدة المنقول منها,المعدة المنقول إليها,التاريخ,ملاحظة\n1,طلمبة ديزل,EX-100,EX-200,2026-11-15,نقل عاجل",
        'to_equipment',
        'EX-200',
    ],
    'car-breakdowns' => [
        'car-breakdowns',
        CarBreakdown::class,
        "م,كود السيارة,نوع السيارة,اللوحة,موديل,المستلم السابق,الرقم الوظيفي,الإدارة,الوظيفة,نوع الوقود,الفرع,الحالة\n1,CAR-901,هايلوكس,ر س 901,2023,ناصر,8811,المشاريع,مراقب,بنزين,نجران,تحتاج ماكينة",
        'car_code',
        'CAR-901',
    ],
    'idle-project-equipment' => [
        'idle-project-equipment',
        IdleProjectEquipment::class,
        "م,كود المعدة,نوع المعدة,اسم السائق,الرقم الوظيفي,الفرع,الموقع,حالة المعدة,إجمالي أيام التوقف\n1,IDL-901,شيول,عمر,9911,تبوك,مشروع الشمال,بدون عمل فعلي,12",
        'equipment_code',
        'IDL-901',
    ],
    'financial-support' => [
        'financial-support',
        FinancialSupportTransfer::class,
        "م,الشهر,نوع المعدة,الكود,المبلغ,اسم المستفيد,نوع العطل,الفرع,الحالة,رقم الـ PO,مرحلة التحويل,تاريخ التحويل,تاريخ التوريد,تاريخ الإصلاح,موقف الإصلاح\n1,11,مولد,FS-901,15000,مؤسسة الخليج,عطل كهرباء,نجران,معتمد,PO-901,تم التحويل,2026-11-10,2026-11-12,2026-11-15,تم الإصلاح",
        'equipment_code',
        'FS-901',
    ],
    'maintenance-deviations' => [
        'maintenance-deviations',
        MaintenanceDeviation::class,
        "التاريخ,نسبة الصيانة اليومية,نسبة الصيانة الوقائية,نسبة انحراف الصيانة اليومية,نسبة انحراف الصيانة الوقائية,ملاحظات\n15,98%,72%,2%,28%,يوم عمل",
        'day_number',
        15,
    ],
]);

it('renders import/purge controls without row actions, downloads template, imports csv directly, and purges all records for every sidebar module', function (
    string $module,
    string $modelClass,
    string $csvContent,
    string $assertColumn,
    string|int $assertValue,
): void {
    $admin = User::factory()->admin()->create();

    // 1. Index renders import & purge buttons without row actions column
    $indexResponse = $this->actingAs($admin)->get(route("{$module}.index"));
    $indexResponse->assertOk()
        ->assertSee('حذف جميع البيانات')
        ->assertSee('استيراد وإسقاط (CSV/Excel)')
        ->assertDontSee('>الإجراءات</th>', false);

    // 2. Download CSV template works
    $templateResponse = $this->actingAs($admin)->get(route("{$module}.import.template"));
    $templateResponse->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    // 3. Direct CSV import drops records accurately
    $file = UploadedFile::fake()->createWithContent("{$module}.csv", $csvContent);
    $importResponse = $this->actingAs($admin)->post(route("{$module}.import.direct"), [
        'file' => $file,
    ]);
    $importResponse->assertRedirect()
        ->assertSessionHas('status');

    expect($modelClass::query()->where($assertColumn, $assertValue)->exists())->toBeTrue();

    // 4. Purge all deletes all records cleanly
    $purgeResponse = $this->actingAs($admin)->delete(route("{$module}.purge-all"));
    $purgeResponse->assertRedirect()
        ->assertSessionHas('status');

    expect($modelClass::query()->count())->toBe(0);
})->with('sidebar_importable_modules');

it('displays live workshop details and status notes from equipment breakdowns on breakdown-stoppages without the 4 top KPI cards', function (): void {
    $admin = User::factory()->admin()->create();

    EquipmentBreakdown::factory()->create([
        'equipment_code' => 'DT_000020',
        'equipment_name' => 'قلاب',
        'workshop_name' => 'ورشه نجران',
        'workshop_status' => 'دعم مالي',
        'fault_description' => 'فرامل وقماشات',
        'estimated_cost' => 1451,
        'expected_repair_date' => '2026-11-29',
        'notes' => 'تم رفع المطالبة المالية وبانتظار الصرف',
    ]);

    BreakdownDriverStoppage::factory()->create([
        'driver_name' => 'أحمد عبدالكريم',
        'employee_id' => '3246',
        'equipment_code' => 'DT_000020',
        'equipment_type' => 'قلاب',
    ]);

    $response = $this->actingAs($admin)->get(route('breakdown-stoppages.index'));

    $response->assertOk()
        ->assertDontSee('توقف حرج (> 3 أيام)')
        ->assertSee('موقف الورش (من كشف الأعطال)')
        ->assertSee('تم رفع المطالبة المالية وبانتظار الصرف')
        ->assertDontSee('تاريخ الإصلاح: 29/11/2026');
});
