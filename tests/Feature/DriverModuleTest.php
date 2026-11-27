<?php

declare(strict_types=1);

use App\Models\Driver;
use App\Models\EquipmentDistribution;
use App\Models\User;
use Illuminate\Http\UploadedFile;

it('imports drivers, auto-links branch and site from equipment distributions, renders summary, filters, exports, and purges', function (): void {
    $admin = User::factory()->admin()->create();

    // Seed matching equipment distributions
    EquipmentDistribution::factory()->create([
        'equipment_code' => 'AP_000008',
        'equipment_type' => 'فرادة اسفلت',
        'branch' => 'نجران',
        'site_location' => 'تشغيل -نجران',
    ]);
    EquipmentDistribution::factory()->create([
        'equipment_code' => 'AR_000005',
        'equipment_type' => 'رصاصة',
        'branch' => 'أبها',
        'site_location' => 'مشروع أحد رفيدة',
    ]);

    // Import Drivers CSV with EMPTY نوع المعده, الفرع and الموقع columns (and a row with blank salary)
    $csv = "\xEF\xBB\xBF"
        ."كود المعدة,نوع المعده,الفرع,الموقع,اسم السائق,الرقم الوظيفي,المهنة,الجنسية,الراتب,الكفيل\n"
        ."AP_000008,,,,رانجيت كومار,3903,سائق فرادة اسفلت,India,2242,كفاله شركة\n"
        ."AR_000005,,,,باراس بال,1789,سائق رصاصة,INDIA,1866,كفاله شركة\n"
        ."BK_000004,,,,عمران محمد,7221,سائق بوبكات,Pakistan,1600,كفاله شركة\n"
        ."DT_000058,,,,وليد جمال عبدالله الشعيبي,5161,سائق فراده,Yemen,,كفاله شركة\n";

    $file = UploadedFile::fake()->createWithContent('drivers.csv', $csv);

    $this->actingAs($admin)
        ->post(route('drivers.import.direct'), ['file' => $file])
        ->assertRedirect(route('drivers.index'));

    expect(Driver::query()->count())->toBe(4);

    $driver1 = Driver::query()->where('equipment_code', 'AP_000008')->firstOrFail();
    $driver2 = Driver::query()->where('equipment_code', 'AR_000005')->firstOrFail();

    expect($driver1->branch)->toBe('نجران')
        ->and($driver1->site_location)->toBe('تشغيل -نجران')
        ->and($driver2->branch)->toBe('أبها')
        ->and($driver2->site_location)->toBe('مشروع أحد رفيدة');

    // Table renders exact columns with auto-linked branch & site values
    $response = $this->actingAs($admin)
        ->get(route('drivers.index'))
        ->assertOk()
        ->assertSee('السائقين')
        ->assertSee('ملخص السائقين')
        ->assertSee('نجران')
        ->assertSee('تشغيل -نجران')
        ->assertSee('فرادة اسفلت')
        ->assertSee('أبها')
        ->assertSee('مشروع أحد رفيدة')
        ->assertSee('رصاصة')
        ->assertSee('5,708');

    $html = $response->getContent();
    preg_match_all('/<th\b[^>]*>(.*?)<\/th>/su', $html, $matches);
    $headers = array_map(static fn (string $h): string => trim(strip_tags($h)), $matches[1]);
    expect($headers)->toBe(['كود المعدة', 'نوع المعده', 'الفرع', 'الموقع', 'اسم السائق', 'الرقم الوظيفي', 'المهنة', 'الجنسية', 'الراتب', 'الكفيل']);

    // Summary tab (?view=summary) merges India + INDIA case-insensitively and renders Job × Branch, Nationality, and Sponsor summaries
    $this->actingAs($admin)
        ->get(route('drivers.index', ['view' => 'summary']))
        ->assertOk()
        ->assertSee('ملخص السائقين حسب المهنة والفرع')
        ->assertSee('ملخص السائقين حسب نوع المعدة')
        ->assertSee('ملخص السائقين حسب الجنسية')
        ->assertSee('ملخص السائقين حسب الكفيل')
        ->assertSee('سائق فرادة اسفلت')
        ->assertSee('India')
        ->assertDontSee('>INDIA<', false)
        ->assertSee('4,108')
        ->assertSee('Pakistan')
        ->assertSee('كفاله شركة')
        ->assertSee('5,708');

    // Dedicated Equipment Type Summary tab (?view=equipment_type_summary)
    $this->actingAs($admin)
        ->get(route('drivers.index', ['view' => 'equipment_type_summary']))
        ->assertOk()
        ->assertSee('ملخص السائقين حسب نوع المعدة والفرع')
        ->assertSee('فرادة اسفلت')
        ->assertSee('رصاصة');

    // Case-insensitive nationality filter returns both India and INDIA rows (2 total)
    $this->actingAs($admin)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('drivers.index', ['nationality' => 'India']))
        ->assertOk()
        ->assertJsonPath('total', 2);

    // Filter by linked equipment type works
    $this->actingAs($admin)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('drivers.index', ['equipment_type' => 'فرادة اسفلت']))
        ->assertOk()
        ->assertJsonPath('total', 1);

    // Filter by linked branch works
    $this->actingAs($admin)
        ->withHeaders(['X-Partial' => 'true', 'Accept' => 'application/json'])
        ->get(route('drivers.index', ['branch' => 'نجران']))
        ->assertOk()
        ->assertJsonPath('total', 1);

    // Export CSV includes linked branch & site
    $exported = $this->actingAs($admin)
        ->get(route('drivers.export'))
        ->assertOk()
        ->streamedContent();

    expect($exported)->toContain('رانجيت كومار')
        ->and($exported)->toContain('تشغيل -نجران');

    // Purge all
    $this->actingAs($admin)
        ->post(route('drivers.purge-all'))
        ->assertRedirect(route('drivers.index'));

    expect(Driver::query()->count())->toBe(0);
});

it('enforces rbac permissions on drivers endpoints', function (): void {
    $viewer = User::factory()->executiveViewer()->create();
    $supervisor = User::factory()->fleetSupervisor()->create();
    $driver = Driver::factory()->create();

    $this->actingAs($viewer)
        ->get(route('drivers.index'))
        ->assertOk();

    $this->actingAs($viewer)
        ->postJson(route('drivers.store'), ['driver_name' => 'أحمد'])
        ->assertForbidden();

    $this->actingAs($supervisor)
        ->postJson(route('drivers.store'), ['driver_name' => 'أحمد'])
        ->assertCreated();

    $this->actingAs($supervisor)
        ->deleteJson(route('drivers.destroy', $driver))
        ->assertForbidden();
});
