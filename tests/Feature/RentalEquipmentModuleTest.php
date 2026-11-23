<?php

declare(strict_types=1);

use App\Models\RentalEquipment;
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

    // 3. Dashboard card "إجمالي معدات الإيجار بالمشاريع" is linked to rental-equipment.index and shows live count (3)
    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('إجمالي معدات الإيجار بالمشاريع')
        ->assertSee(route('rental-equipment.index'))
        ->assertSeeInOrder(['إجمالي معدات الإيجار بالمشاريع', '3']);

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
