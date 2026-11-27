<?php

declare(strict_types=1);

use App\Models\CarRegulatoryDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;

it('manages car regulatory documents tabs, scoped import/purge, and syncs live counts to dashboard card', function (): void {
    $admin = User::factory()->admin()->create();
    $expiredDate = today()->subDays(15)->format('Y-m-d');

    // 1. Scoped CSV import into "فحص دوري" tab
    $csvInspection = "\xEF\xBB\xBF"
        ."م,كود السيارة,رقم اللوحة,نوع السيارة والموديل,الفرع,المشروع / الموقع,تاريخ الانتهاء (هجري),تاريخ الانتهاء (ميلادي)\n"
        ."1,C_000205,أ ل ر 9882,ميتسوبيشي غمارتين (2011),أبها,مشروع باللحمر,1448-06-01,{$expiredDate}\n"
        ."2,C_000182,أ ق م 5214,تويوتا هايلكس (2018),البحر الأحمر,مشروع السلاحف,1448-03-05,{$expiredDate}\n";

    $file = UploadedFile::fake()->createWithContent('car-inspection.csv', $csvInspection);

    $this->actingAs($admin)
        ->post(route('car-regulatory-documents.import.direct'), [
            'file' => $file,
            'document_type' => 'فحص دوري',
        ])
        ->assertRedirect(route('car-regulatory-documents.index', ['document_type' => 'فحص دوري']));

    // 2. Add 1 expired registration and 1 expired insurance
    $this->actingAs($admin)
        ->postJson(route('car-regulatory-documents.store'), [
            'document_type' => 'استمارة',
            'car_code' => 'C_000300',
            'branch' => 'الجوف',
            'expiry_date' => $expiredDate,
        ])
        ->assertCreated();

    $this->actingAs($admin)
        ->postJson(route('car-regulatory-documents.store'), [
            'document_type' => 'تأمين',
            'car_code' => 'C_000400',
            'branch' => 'نجران',
            'expiry_date' => $expiredDate,
        ])
        ->assertCreated();

    expect(CarRegulatoryDocument::query()->where('document_type', 'فحص دوري')->count())->toBe(2)
        ->and(CarRegulatoryDocument::query()->where('document_type', 'استمارة')->count())->toBe(1)
        ->and(CarRegulatoryDocument::query()->where('document_type', 'تأمين')->count())->toBe(1);

    // 3. Index renders tabs & exact imported columns, while model still auto-calculates expired days
    $this->actingAs($admin)
        ->get(route('car-regulatory-documents.index', ['document_type' => 'فحص دوري']))
        ->assertOk()
        ->assertSee('الأوراق النظامية للسيارات')
        ->assertSee('الفحص الدوري المنتهي')
        ->assertSee('الاستمارات المنتهية')
        ->assertSee('التأمين المنتهي')
        ->assertSee('C_000205')
        ->assertSee($expiredDate);

    expect(CarRegulatoryDocument::query()->where('car_code', 'C_000205')->firstOrFail()->days_remaining)
        ->toBe(-15);

    // 4. Dashboard card "موقف الأوراق النظامية للسيارات" shows live counts (2, 1, 1) and deep links
    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('موقف الأوراق النظامية للسيارات')
        ->assertSee(route('car-regulatory-documents.index', ['document_type' => 'فحص دوري']))
        ->assertSee(route('car-regulatory-documents.index', ['document_type' => 'استمارة']))
        ->assertSee(route('car-regulatory-documents.index', ['document_type' => 'تأمين']));

    // 5. Scoped purge via POST deletes ONLY "فحص دوري" without touching "استمارة" or "تأمين"
    $this->actingAs($admin)
        ->post(route('car-regulatory-documents.purge-all'), ['document_type' => 'فحص دوري'])
        ->assertRedirect(route('car-regulatory-documents.index', ['document_type' => 'فحص دوري']));

    expect(CarRegulatoryDocument::query()->where('document_type', 'فحص دوري')->count())->toBe(0)
        ->and(CarRegulatoryDocument::query()->where('document_type', 'استمارة')->count())->toBe(1)
        ->and(CarRegulatoryDocument::query()->where('document_type', 'تأمين')->count())->toBe(1);
});

it('enforces rbac permissions on car regulatory documents endpoints', function (): void {
    $viewer = User::factory()->executiveViewer()->create();
    $supervisor = User::factory()->fleetSupervisor()->create();
    $record = CarRegulatoryDocument::factory()->create();

    $this->actingAs($viewer)
        ->get(route('car-regulatory-documents.index'))
        ->assertOk();

    $this->actingAs($viewer)
        ->postJson(route('car-regulatory-documents.store'), ['car_code' => 'C_1'])
        ->assertForbidden();

    $this->actingAs($supervisor)
        ->postJson(route('car-regulatory-documents.store'), ['car_code' => 'C_2'])
        ->assertCreated();

    $this->actingAs($supervisor)
        ->deleteJson(route('car-regulatory-documents.destroy', $record))
        ->assertForbidden();
});
