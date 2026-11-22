<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\EquipmentBreakdown;
use App\Models\OwnershipTransfer;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('renders arabic rtl login page and authenticates valid active users', function (): void {
    $user = User::factory()->admin()->create([
        'email' => 'admin.test@fleet.local',
        'password' => 'secret1234',
    ]);

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('lang="ar"', false)
        ->assertSee('تسجيل الدخول');

    $this->post(route('login.store'), [
        'email' => 'admin.test@fleet.local',
        'password' => 'secret1234',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();

    // Verify authentication using Arabic name 'هيثم' directly
    $haithemUser = User::factory()->admin()->create([
        'name' => 'هيثم',
        'email' => 'haithem.admin@fleet.local',
        'password' => '123456***',
    ]);

    $this->post(route('login.store'), [
        'email' => 'هيثم',
        'password' => '123456***',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($haithemUser);

    $this->post(route('logout'));
    $this->assertGuest();
});

it('blocks invalid credentials and inactive accounts from signing in or staying authenticated', function (): void {
    $inactiveUser = User::factory()->fleetSupervisor()->inactive()->create([
        'email' => 'inactive@fleet.local',
        'password' => 'secret1234',
    ]);

    $this->post(route('login.store'), [
        'email' => 'inactive@fleet.local',
        'password' => 'secret1234',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();

    // Even if authenticated beforehand, EnsureUserIsActive middleware logs them out
    $this->actingAs($inactiveUser)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('enforces rbac policies accurately across all four user roles', function (): void {
    $admin = User::factory()->admin()->create();
    $opsManager = User::factory()->operationsManager()->create();
    $supervisor = User::factory()->fleetSupervisor()->create();
    $viewer = User::factory()->executiveViewer()->create();
    $inactiveAdmin = User::factory()->admin()->inactive()->create();

    $transfer = OwnershipTransfer::factory()->create();
    $breakdown = EquipmentBreakdown::factory()->create();
    $auditLog = AuditLog::query()->firstOrFail();

    // Admin: full privileges
    expect($admin->can('viewAny', OwnershipTransfer::class))->toBeTrue()
        ->and($admin->can('create', OwnershipTransfer::class))->toBeTrue()
        ->and($admin->can('update', $transfer))->toBeTrue()
        ->and($admin->can('delete', $transfer))->toBeTrue()
        ->and($admin->can('forceDelete', $transfer))->toBeTrue()
        ->and($admin->can('viewAny', AuditLog::class))->toBeTrue()
        ->and($admin->can('delete', $auditLog))->toBeFalse();

    // Operations Manager: create/update/soft-delete + audit logs, no forceDelete
    expect($opsManager->can('create', EquipmentBreakdown::class))->toBeTrue()
        ->and($opsManager->can('update', $breakdown))->toBeTrue()
        ->and($opsManager->can('delete', $breakdown))->toBeTrue()
        ->and($opsManager->can('forceDelete', $breakdown))->toBeFalse()
        ->and($opsManager->can('viewAny', AuditLog::class))->toBeTrue();

    // Fleet Supervisor: create/update, no delete, no audit logs
    expect($supervisor->can('viewAny', EquipmentBreakdown::class))->toBeTrue()
        ->and($supervisor->can('create', EquipmentBreakdown::class))->toBeTrue()
        ->and($supervisor->can('update', $breakdown))->toBeTrue()
        ->and($supervisor->can('delete', $breakdown))->toBeFalse()
        ->and($supervisor->can('viewAny', AuditLog::class))->toBeFalse();

    // Executive Viewer: read-only
    expect($viewer->can('viewAny', OwnershipTransfer::class))->toBeTrue()
        ->and($viewer->can('view', $transfer))->toBeTrue()
        ->and($viewer->can('create', OwnershipTransfer::class))->toBeFalse()
        ->and($viewer->can('update', $transfer))->toBeFalse()
        ->and($viewer->can('delete', $transfer))->toBeFalse()
        ->and($viewer->can('viewAny', AuditLog::class))->toBeFalse();

    // Inactive admin: denied everywhere
    expect($inactiveAdmin->can('viewAny', OwnershipTransfer::class))->toBeFalse()
        ->and(Gate::forUser($inactiveAdmin)->allows('view-dashboard'))->toBeFalse();
});
