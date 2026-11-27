<?php

use App\Enums\UserRole;
use App\Models\EquipmentBreakdown;
use App\Models\RentalEquipment;
use App\Models\User;
use App\Support\ExtraColumns;
use Illuminate\Http\UploadedFile;

it('renders only the exact imported columns in their exact file order with no extra or missing columns', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $csv = implode("\n", [
        'رقم العقد,الفرع,اسم المقاول,المشرف المسؤول,مبلغ الإيجار',
        'CNT-2026-99,أبها,شركة الاختبار,م. فهد العتيبي,18500',
    ]);

    $file = UploadedFile::fake()->createWithContent('rentals-exact-order.csv', "\xEF\xBB\xBF".$csv);

    $this->actingAs($admin)
        ->post(route('rental-equipment.import.direct'), [
            'file' => $file,
        ])
        ->assertRedirect();

    $record = RentalEquipment::query()->firstOrFail();

    expect($record->contractor_name)->toBe('شركة الاختبار')
        ->and($record->extra_columns)->toBe([
            ExtraColumns::EXACT_KEY => [
                'رقم العقد' => 'CNT-2026-99',
                'الفرع' => 'أبها',
                'اسم المقاول' => 'شركة الاختبار',
                'المشرف المسؤول' => 'م. فهد العتيبي',
                'مبلغ الإيجار' => '18500',
            ],
        ]);

    $response = $this->actingAs($admin)
        ->get(route('rental-equipment.index'))
        ->assertOk();

    // Exact order of headers and values
    $response->assertSeeInOrder([
        'رقم العقد',
        'الفرع',
        'اسم المقاول',
        'المشرف المسؤول',
        'مبلغ الإيجار',
    ]);

    $response->assertSeeInOrder([
        'CNT-2026-99',
        'أبها',
        'شركة الاختبار',
        'م. فهد العتيبي',
        '18,500',
    ]);

    // Default columns NOT in the CSV must NOT appear in table headers
    $html = $response->getContent();
    preg_match_all('/<th\b[^>]*>(.*?)<\/th>/su', $html, $matches);
    $renderedHeaders = array_map(static fn (string $h): string => trim(strip_tags($h)), $matches[1]);

    expect($renderedHeaders)->toBe([
        'رقم العقد',
        'الفرع',
        'اسم المقاول',
        'المشرف المسؤول',
        'مبلغ الإيجار',
    ]);
});

it('renders exact imported columns in exact order for equipment breakdowns', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $csv = implode("\n", [
        'رقم أمر العمل,كود المعدة,المعدة,المهندس المتابع,الورشة',
        'WO-7788,EQ_9901,شيول,م. خالد الحربي,الورشة الرئيسية',
    ]);

    $file = UploadedFile::fake()->createWithContent('breakdowns-exact.csv', "\xEF\xBB\xBF".$csv);

    $this->actingAs($admin)
        ->post(route('equipment-breakdowns.import.direct'), [
            'file' => $file,
        ])
        ->assertRedirect();

    $record = EquipmentBreakdown::query()->where('equipment_code', 'EQ_9901')->firstOrFail();

    expect($record->extra_columns)->toBe([
        ExtraColumns::EXACT_KEY => [
            'رقم أمر العمل' => 'WO-7788',
            'كود المعدة' => 'EQ_9901',
            'المعدة' => 'شيول',
            'المهندس المتابع' => 'م. خالد الحربي',
            'الورشة' => 'الورشة الرئيسية',
        ],
    ]);

    $response = $this->actingAs($admin)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('equipment-breakdowns.index'))
        ->assertOk();

    $html = (string) $response->json('table_html');
    preg_match_all('/<th\b[^>]*>(.*?)<\/th>/su', $html, $matches);
    $renderedHeaders = array_map(static fn (string $h): string => trim(strip_tags($h)), $matches[1]);

    expect($renderedHeaders)->toBe([
        'رقم أمر العمل',
        'كود المعدة',
        'المعدة',
        'المهندس المتابع',
        'الورشة',
    ]);
});

it('allows importing files containing only custom columns and shows only those columns in exact order', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $csv = implode("\n", [
        'الجهة الطالبة,تصنيف خاص,الأولوية',
        'إدارة المشاريع,مهمة ميدانية,قصوى',
    ]);

    $file = UploadedFile::fake()->createWithContent('custom-only.csv', "\xEF\xBB\xBF".$csv);

    $this->actingAs($admin)
        ->post(route('unassigned-drivers.import.direct'), [
            'file' => $file,
        ])
        ->assertRedirect();

    $response = $this->actingAs($admin)
        ->get(route('unassigned-drivers.index'))
        ->assertOk();

    $html = $response->getContent();
    preg_match_all('/<th\b[^>]*>(.*?)<\/th>/su', $html, $matches);
    $renderedHeaders = array_map(static fn (string $h): string => trim(strip_tags($h)), $matches[1]);

    expect($renderedHeaders)->toBe([
        'الجهة الطالبة',
        'تصنيف خاص',
        'الأولوية',
    ]);

    $response->assertSeeInOrder([
        'إدارة المشاريع',
        'مهمة ميدانية',
        'قصوى',
    ]);
});
