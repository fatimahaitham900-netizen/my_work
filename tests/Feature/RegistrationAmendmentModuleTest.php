<?php

declare(strict_types=1);

use App\Models\RegistrationAmendment;
use App\Models\User;
use Illuminate\Http\UploadedFile;

it('auto-calculates stoppage days, imports/purges csv, and syncs live count to dashboard card', function (): void {
    $admin = User::factory()->admin()->create();
    $tenDaysAgo = today()->subDays(10)->format('Y-m-d');
    $threeDaysAgo = today()->subDays(3)->format('Y-m-d');

    $csvContent = "\xEF\xBB\xBF"
        ."م,الكود,نوع المعدة,الفرع,تاريخ التوقف\n"
        ."1,DT_000033,قلاب,نجران,{$tenDaysAgo}\n"
        ."2,TR_000011,تريلا,الرياض,{$threeDaysAgo}\n";

    $file = UploadedFile::fake()->createWithContent('amendments.csv', $csvContent);

    $this->actingAs($admin)
        ->post(route('registration-amendments.import.direct'), ['file' => $file])
        ->assertRedirect(route('registration-amendments.index'));

    expect(RegistrationAmendment::query()->count())->toBe(2);

    $this->actingAs($admin)
        ->get(route('registration-amendments.index'))
        ->assertOk()
        ->assertSee('معدات تعديل استمارة')
        ->assertSee('DT_000033')
        ->assertSee($tenDaysAgo)
        ->assertSee($threeDaysAgo);

    expect(RegistrationAmendment::query()->where('equipment_code', 'DT_000033')->firstOrFail()->stoppage_days)->toBe(10)
        ->and(RegistrationAmendment::query()->where('equipment_code', 'TR_000011')->firstOrFail()->stoppage_days)->toBe(3);

    // Dashboard card "معدات تحتاج إلى تعديل استمارة" is linked and shows live count (2)
    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('معدات تحتاج إلى تعديل استمارة')
        ->assertSee(route('registration-amendments.index'))
        ->assertSeeInOrder(['معدات تحتاج إلى تعديل استمارة', '2']);

    // Create new record and verify auto-calculated stoppage_days
    $this->actingAs($admin)
        ->postJson(route('registration-amendments.store'), [
            'equipment_code' => 'WL_000050',
            'equipment_type' => 'شيول',
            'branch' => 'مكة',
            'stoppage_date' => today()->subDays(5)->format('Y-m-d'),
        ])
        ->assertCreated();

    $created = RegistrationAmendment::query()->where('equipment_code', 'WL_000050')->firstOrFail();
    expect($created->stoppage_days)->toBe(5);

    // Purge all
    $this->actingAs($admin)
        ->delete(route('registration-amendments.purge-all'))
        ->assertRedirect(route('registration-amendments.index'));

    expect(RegistrationAmendment::query()->count())->toBe(0);
});

it('enforces rbac permissions on registration amendments endpoints', function (): void {
    $viewer = User::factory()->executiveViewer()->create();
    $supervisor = User::factory()->fleetSupervisor()->create();
    $record = RegistrationAmendment::factory()->create();

    $this->actingAs($viewer)
        ->get(route('registration-amendments.index'))
        ->assertOk();

    $this->actingAs($viewer)
        ->postJson(route('registration-amendments.store'), ['equipment_code' => 'EQ-1'])
        ->assertForbidden();

    $this->actingAs($supervisor)
        ->postJson(route('registration-amendments.store'), ['equipment_code' => 'EQ-2'])
        ->assertCreated();

    $this->actingAs($supervisor)
        ->deleteJson(route('registration-amendments.destroy', $record))
        ->assertForbidden();
});
