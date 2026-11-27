<?php

declare(strict_types=1);

use App\Models\AccidentClaim;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\UploadedFile;

it('manages separate accidents and insurance claims tabs with scoped import and purge', function (): void {
    $admin = User::factory()->admin()->create();

    // 1. Default tab is الحوادث with columns: م | كود المستخدم | الفرع | السجل | ملاحظات
    $this->actingAs($admin)
        ->get(route('accident-claims.index'))
        ->assertOk()
        ->assertSee('الحوادث ومطالبة التأمين')
        ->assertSee('جدول سجل الحوادث')
        ->assertSee('كود المستخدم')
        ->assertSee('السجل')
        ->assertSee('سجل الحوادث نظيف ولا توجد حوادث مسجلة');

    // 2. Scoped import into الحوادث tab
    $accidentsCsv = "\xEF\xBB\xBF"
        ."م,كود المستخدم,الفرع,السجل,ملاحظات\n"
        ."1,USR-101,الرياض,شركة المقاولات,حادث بسيط\n"
        ."2,USR-202,مكة,المؤسسة,تم التقييم\n";

    $this->actingAs($admin)
        ->post(route('accident-claims.import.direct'), [
            'file' => UploadedFile::fake()->createWithContent('accidents.csv', $accidentsCsv),
            'record_type' => AccidentClaim::TYPE_ACCIDENT,
        ])
        ->assertRedirect(route('accident-claims.index', ['record_type' => AccidentClaim::TYPE_ACCIDENT]));

    // 3. Scoped import into مطالبات التأمين tab
    $claimsCsv = "\xEF\xBB\xBF"
        ."كود السيارة,رقم اللوحة,تاريخ الحادث,موقع الحادث,الفرع,السائق,رقم تقرير نجم,رقم المطالبة,شركة التأمين,نسبة المسؤولية,التكلفة التقديرية,مبلغ التعويض,حالة الحادث\n"
        ."C_000120,أ ب ج 4521,2026-11-10,طريق الملك فهد,الرياض,أحمد محمد,NJM-884120,CLM-55210,التعاونية,0,12500,12500,تم التعويض\n"
        ."C_000250,د هـ و 7711,2026-11-20,مشروع ولي العهد,مكة,سعيد علي,NJM-991002,CLM-66120,ولاء,50,8000,,تحت الإجراء\n";

    $this->actingAs($admin)
        ->post(route('accident-claims.import.direct'), [
            'file' => UploadedFile::fake()->createWithContent('claims.csv', $claimsCsv),
            'record_type' => AccidentClaim::TYPE_INSURANCE_CLAIM,
        ])
        ->assertRedirect(route('accident-claims.index', ['record_type' => AccidentClaim::TYPE_INSURANCE_CLAIM]));

    expect(AccidentClaim::query()->where('record_type', AccidentClaim::TYPE_ACCIDENT)->count())->toBe(2)
        ->and(AccidentClaim::query()->where('record_type', AccidentClaim::TYPE_INSURANCE_CLAIM)->count())->toBe(2);

    // 4. Accidents tab renders ONLY accidents, Insurance Claims tab renders ONLY claims
    $this->actingAs($admin)
        ->get(route('accident-claims.index', ['record_type' => AccidentClaim::TYPE_ACCIDENT]))
        ->assertOk()
        ->assertSee('USR-101')
        ->assertDontSee('NJM-884120');

    $this->actingAs($admin)
        ->get(route('accident-claims.index', ['record_type' => AccidentClaim::TYPE_INSURANCE_CLAIM]))
        ->assertOk()
        ->assertSee('NJM-884120')
        ->assertSee('20,500')
        ->assertDontSee('USR-101');

    // 5. Scoped purge of الحوادث does NOT delete مطالبات التأمين
    $this->actingAs($admin)
        ->post(route('accident-claims.purge-all'), ['record_type' => AccidentClaim::TYPE_ACCIDENT])
        ->assertRedirect(route('accident-claims.index', ['record_type' => AccidentClaim::TYPE_ACCIDENT]));

    expect(AccidentClaim::query()->where('record_type', AccidentClaim::TYPE_ACCIDENT)->count())->toBe(0)
        ->and(AccidentClaim::query()->where('record_type', AccidentClaim::TYPE_INSURANCE_CLAIM)->count())->toBe(2);
});

it('enforces rbac permissions on accident claims endpoints', function (): void {
    $viewer = User::factory()->executiveViewer()->create();
    $supervisor = User::factory()->fleetSupervisor()->create();
    $claim = AccidentClaim::factory()->create();

    $this->actingAs($viewer)
        ->get(route('accident-claims.index'))
        ->assertOk();

    $this->actingAs($viewer)
        ->postJson(route('accident-claims.store'), ['user_code' => 'USR-1'])
        ->assertForbidden();

    $this->actingAs($supervisor)
        ->postJson(route('accident-claims.store'), ['user_code' => 'USR-1', 'record_type' => AccidentClaim::TYPE_ACCIDENT])
        ->assertCreated();

    $this->actingAs($supervisor)
        ->deleteJson(route('accident-claims.destroy', $claim))
        ->assertForbidden();
});

it('displays unclosed accidents and unrecovered insurance amounts on the executive dashboard', function (): void {
    $admin = User::factory()->admin()->create();

    AccidentClaim::factory()->create([
        'record_type' => AccidentClaim::TYPE_ACCIDENT,
        'claim_status' => 'تحت الإجراء',
    ]);
    AccidentClaim::factory()->create([
        'record_type' => AccidentClaim::TYPE_ACCIDENT,
        'claim_status' => null,
    ]);
    AccidentClaim::factory()->create([
        'record_type' => AccidentClaim::TYPE_ACCIDENT,
        'claim_status' => 'مغلق',
    ]);

    AccidentClaim::factory()->create([
        'record_type' => AccidentClaim::TYPE_INSURANCE_CLAIM,
        'estimated_cost' => 15000,
        'compensation_amount' => 2500,
        'claim_status' => 'تحت الإجراء',
    ]);
    AccidentClaim::factory()->create([
        'record_type' => AccidentClaim::TYPE_INSURANCE_CLAIM,
        'estimated_cost' => 9000,
        'compensation_amount' => 9000,
        'claim_status' => 'تم التعويض',
    ]);

    DashboardService::clearCache();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('موقف الحوادث والتأمينات')
        ->assertSee('حوادث لم تغلق')
        ->assertSee('مبالغ من التأمين غير مستردة')
        ->assertSee('12,500');
});
