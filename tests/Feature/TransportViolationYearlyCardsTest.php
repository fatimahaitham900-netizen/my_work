<?php

use App\Enums\UserRole;
use App\Models\TransportViolation;
use App\Models\User;

it('renders a separate dashboard card per year for transport violations with count and amount and filters by year', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    TransportViolation::factory()->create([
        'violation_number' => 'VIO-2026-1',
        'violation_date' => '2026-03-15',
        'amount' => 15000,
    ]);
    TransportViolation::factory()->create([
        'violation_number' => 'VIO-2026-2',
        'violation_date' => '2026-08-20',
        'amount' => 5000,
    ]);
    TransportViolation::factory()->create([
        'violation_number' => 'VIO-2025-1',
        'violation_date' => '2025-05-10',
        'amount' => 42000,
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('مخالفات وزارة النقل')
        ->assertSeeInOrder(['2025', '2026', 'الإجمالي'])
        ->assertSee('42,000')
        ->assertSee('20,000')
        ->assertSee('62,000')
        ->assertSee('إجمالي القيمة (ر.س)')
        ->assertSee(route('transport-violations.index', ['year' => '2026']))
        ->assertSee(route('transport-violations.index', ['year' => '2025']));

    $this->actingAs($admin)
        ->get(route('transport-violations.index', ['year' => '2026']))
        ->assertOk()
        ->assertSee('VIO-2026-1')
        ->assertSee('VIO-2026-2')
        ->assertDontSee('VIO-2025-1');
});
