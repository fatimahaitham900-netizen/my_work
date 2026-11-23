<?php

declare(strict_types=1);

use App\Enums\AuditEvent;
use App\Enums\ComplianceStatus;
use App\Enums\RegulatoryEntityType;
use App\Models\AuditLog;
use App\Models\RegulatoryDocument;
use App\Models\User;
use Database\Seeders\RegulatoryDocumentsRealDataSeeder;

it('seeds all 293 expired regulatory documents across periodic inspection, operating cards, and registrations with dashboard indicator boxes', function (): void {
    $this->seed(RegulatoryDocumentsRealDataSeeder::class);
    $admin = User::factory()->admin()->create();

    expect(RegulatoryDocument::query()->count())->toBe(293)
        ->and(RegulatoryDocument::query()->expiredPeriodicInspections()->count())->toBe(131)
        ->and(RegulatoryDocument::query()->expiredOperatingCards()->count())->toBe(80)
        ->and(RegulatoryDocument::query()->expiredRegistrations()->count())->toBe(82);

    $firstInspection = RegulatoryDocument::query()
        ->where('document_type', 'فحص دوري')
        ->where('entity_identifier', 'DT_000033')
        ->firstOrFail();

    $firstOpCard = RegulatoryDocument::query()
        ->where('document_type', 'كرت تشغيل')
        ->where('entity_identifier', 'TR_000011')
        ->firstOrFail();

    $lastOpCard = RegulatoryDocument::query()
        ->where('document_type', 'كرت تشغيل')
        ->where('entity_identifier', 'TRL_000006')
        ->firstOrFail();

    $firstRegistration = RegulatoryDocument::query()
        ->where('document_type', 'استمارة')
        ->where('entity_identifier', 'MC_000005')
        ->firstOrFail();

    $lastRegistration = RegulatoryDocument::query()
        ->where('document_type', 'استمارة')
        ->where('entity_identifier', 'DT_000068')
        ->firstOrFail();

    expect($firstInspection->document_number)->toBe('63523900')
        ->and($firstInspection->hijri_expiry_date)->toBe('05/01/1435')
        ->and($firstInspection->expiry_date?->format('Y-m-d'))->toBe('2013-11-08')
        ->and($firstOpCard->document_number)->toBe('542558800')
        ->and($firstOpCard->renewal_status)->toBe('مطلوب تجديد')
        ->and($lastOpCard->renewal_status)->toBe('عمر افتراضي')
        ->and($lastOpCard->expiry_date?->format('Y-m-d'))->toBe('2026-09-25')
        ->and($firstRegistration->document_number)->toBe('272683110')
        ->and($firstRegistration->hijri_expiry_date)->toBe('05/05/1436')
        ->and($lastRegistration->expiry_date?->format('Y-m-d'))->toBe('2026-11-15')
        ->and($lastRegistration->renewal_status)->toBe('مطلوب تجديد');

    $inspectionLink = route('regulatory-documents.index', [
        'document_type' => 'فحص دوري',
        'compliance_status' => ComplianceStatus::Expired->value,
    ]);
    $opCardsLink = route('regulatory-documents.index', [
        'document_type' => 'كرت تشغيل',
        'compliance_status' => ComplianceStatus::Expired->value,
    ]);
    $registrationsLink = route('regulatory-documents.index', [
        'document_type' => 'استمارة',
        'compliance_status' => ComplianceStatus::Expired->value,
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('معدات منتهية الفحص الدوري')
        ->assertSee('كروت تشغيل منتهية معدات')
        ->assertSee('استمارات منتهية معدات')
        ->assertSee('131')
        ->assertSee('80')
        ->assertSee('82')
        ->assertSee($inspectionLink)
        ->assertSee($opCardsLink)
        ->assertSee($registrationsLink);

    $this->actingAs($admin)
        ->get($opCardsLink)
        ->assertOk()
        ->assertSee('كروت التشغيل المنتهية')
        ->assertSee('الاستمارات المنتهية')
        ->assertSee('تاريخ كارت التشغيل (هـ)')
        ->assertSee('TR_000011')
        ->assertSee('مطلوب تجديد')
        ->assertSee('عمر افتراضي');

    $this->actingAs($admin)
        ->get($registrationsLink)
        ->assertOk()
        ->assertSee('تاريخ الاستمارة (هـ)')
        ->assertSee('MC_000005');

    // Summary page grouped by equipment type across فحص منتهي، استمارات منتهية، كروت منتهية
    $this->actingAs($admin)
        ->get(route('regulatory-documents.index', ['view' => 'summary']))
        ->assertOk()
        ->assertSee('ملخص الأوراق النظامية المنتهية حسب نوع المعدة')
        ->assertSee('فحص منتهي')
        ->assertSee('استمارات منتهية')
        ->assertSee('كروت منتهية')
        ->assertSee('131')
        ->assertSee('82')
        ->assertSee('80')
        ->assertSee('293');

    // Filter sub-pages & renewal_status via XHR
    $this->actingAs($admin)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('regulatory-documents.index', [
            'document_type' => 'كرت تشغيل',
            'renewal_status' => 'عمر افتراضي',
        ]))
        ->assertOk()
        ->assertJsonPath('total', 8);

    $this->actingAs($admin)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('regulatory-documents.index', [
            'document_type' => 'استمارة',
            'renewal_status' => 'عمر افتراضي',
        ]))
        ->assertOk()
        ->assertJsonPath('total', 10);
});

it('auto-classifies regulatory documents into expired, expiring_soon, valid, and missing based on expiry_date', function (): void {
    $admin = User::factory()->admin()->create();

    // 1. Expired (< 0 days)
    $this->actingAs($admin)
        ->postJson(route('regulatory-documents.store'), [
            'entity_type' => RegulatoryEntityType::Equipment->value,
            'entity_identifier' => 'EQ-DOC-EXP',
            'document_type' => 'استمارة',
            'document_number' => 'DOC-001',
            'issue_date' => today()->subYear()->subDays(5)->format('Y-m-d'),
            'expiry_date' => today()->subDays(5)->format('Y-m-d'),
            'compliance_status' => ComplianceStatus::Valid->value, // intentionally stale, must auto-override
            'renewal_cost' => 1200,
        ])
        ->assertCreated();

    // 2. Expiring Soon (0..30 days)
    $this->actingAs($admin)
        ->postJson(route('regulatory-documents.store'), [
            'entity_type' => RegulatoryEntityType::Equipment->value,
            'entity_identifier' => 'EQ-DOC-SOON',
            'document_type' => 'فحص دوري',
            'document_number' => 'DOC-002',
            'issue_date' => today()->subMonths(11)->format('Y-m-d'),
            'expiry_date' => today()->addDays(15)->format('Y-m-d'),
            'renewal_cost' => 600,
        ])
        ->assertCreated();

    // 3. Valid (> 30 days)
    $this->actingAs($admin)
        ->postJson(route('regulatory-documents.store'), [
            'entity_type' => RegulatoryEntityType::Driver->value,
            'entity_identifier' => 'EMP-DOC-OK',
            'document_type' => 'بطاقة سائق',
            'document_number' => 'DOC-003',
            'issue_date' => today()->subMonth()->format('Y-m-d'),
            'expiry_date' => today()->addDays(90)->format('Y-m-d'),
            'renewal_cost' => 300,
        ])
        ->assertCreated();

    // 4. Missing (null expiry)
    $this->actingAs($admin)
        ->postJson(route('regulatory-documents.store'), [
            'entity_type' => RegulatoryEntityType::Equipment->value,
            'entity_identifier' => 'EQ-DOC-MISS',
            'document_type' => 'تأمين',
            'compliance_status' => ComplianceStatus::Missing->value,
            'renewal_cost' => 4500,
        ])
        ->assertCreated();

    $expired = RegulatoryDocument::query()->where('entity_identifier', 'EQ-DOC-EXP')->firstOrFail();
    $soon = RegulatoryDocument::query()->where('entity_identifier', 'EQ-DOC-SOON')->firstOrFail();
    $valid = RegulatoryDocument::query()->where('entity_identifier', 'EMP-DOC-OK')->firstOrFail();
    $missing = RegulatoryDocument::query()->where('entity_identifier', 'EQ-DOC-MISS')->firstOrFail();

    expect($expired->compliance_status)->toBe(ComplianceStatus::Expired)
        ->and($expired->days_remaining)->toBe(-5)
        ->and($soon->compliance_status)->toBe(ComplianceStatus::ExpiringSoon)
        ->and($soon->days_remaining)->toBe(15)
        ->and($valid->compliance_status)->toBe(ComplianceStatus::Valid)
        ->and($valid->days_remaining)->toBe(90)
        ->and($missing->compliance_status)->toBe(ComplianceStatus::Missing)
        ->and($missing->days_remaining)->toBeNull();

    // Visual badges rendered in HTML
    $this->actingAs($admin)
        ->get(route('regulatory-documents.index'))
        ->assertOk()
        ->assertSee('منتهي منذ 5 يوم')
        ->assertSee('متبقي 15 يوم')
        ->assertSee('وثيقة مفقودة');
});

it('validates, filters, updates, soft-deletes, audits, and exports regulatory documents csv', function (): void {
    $admin = User::factory()->admin()->create();
    $viewer = User::factory()->executiveViewer()->create();

    // Validation error: expiry_date before issue_date & negative renewal_cost
    $this->actingAs($admin)
        ->postJson(route('regulatory-documents.store'), [
            'entity_type' => RegulatoryEntityType::Equipment->value,
            'entity_identifier' => 'EQ-BAD-1',
            'document_type' => 'استمارة',
            'issue_date' => today()->format('Y-m-d'),
            'expiry_date' => today()->subDays(10)->format('Y-m-d'),
            'renewal_cost' => -50,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['expiry_date', 'renewal_cost']);

    $doc = RegulatoryDocument::factory()->expired(10)->create(['entity_identifier' => 'EQ-REN-99']);
    RegulatoryDocument::factory()->valid()->create(['entity_identifier' => 'EQ-VAL-11']);

    // Partial filter endpoint
    $this->actingAs($admin)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('regulatory-documents.index', ['compliance_status' => 'expired']))
        ->assertOk()
        ->assertJsonPath('total', 1);

    // Renewing document automatically reclassifies from expired to valid
    $this->actingAs($admin)
        ->putJson(route('regulatory-documents.update', $doc), [
            'entity_type' => $doc->entity_type->value,
            'entity_identifier' => $doc->entity_identifier,
            'document_type' => $doc->document_type,
            'document_number' => 'REN-2026-555',
            'issue_date' => today()->format('Y-m-d'),
            'expiry_date' => today()->addYear()->format('Y-m-d'),
            'compliance_status' => ComplianceStatus::Expired->value,
            'renewal_cost' => 1100,
            'notes' => 'تم التجديد بنجاح',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'تم التحديث بنجاح');

    expect($doc->fresh()->compliance_status)->toBe(ComplianceStatus::Valid)
        ->and($doc->auditLogs()->where('event', AuditEvent::Updated)->count())->toBe(1);

    // Filtered CSV export + Exported audit entry
    $csv = $this->actingAs($admin)
        ->get(route('regulatory-documents.export', ['search' => 'EQ-REN-99']))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('EQ-REN-99')
        ->and($csv)->not->toContain('EQ-VAL-11')
        ->and(AuditLog::query()->where('event', AuditEvent::Exported)->where('auditable_type', RegulatoryDocument::class)->count())->toBe(1);

    // Viewer cannot delete; Admin can soft-delete
    $this->actingAs($viewer)->deleteJson(route('regulatory-documents.destroy', $doc))->assertForbidden();
    $this->actingAs($admin)->deleteJson(route('regulatory-documents.destroy', $doc))->assertOk();

    expect($doc->fresh()->trashed())->toBeTrue();
});
