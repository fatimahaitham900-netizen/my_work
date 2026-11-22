<?php

declare(strict_types=1);

use App\Enums\OwnershipTransferStatus;
use App\Models\BreakdownDriverStoppage;
use App\Models\EquipmentBreakdown;
use App\Models\OwnershipTransfer;
use App\Models\RegulatoryDocument;
use App\Models\UnassignedDriver;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-11-22 10:00:00'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('computes idle_days on BreakdownDriverStoppage accurately', function (): void {
    $pastStoppage = new BreakdownDriverStoppage([
        'stoppage_start_date' => '2026-11-12',
    ]);
    $todayStoppage = new BreakdownDriverStoppage([
        'stoppage_start_date' => '2026-11-22',
    ]);
    $futureStoppage = new BreakdownDriverStoppage([
        'stoppage_start_date' => '2026-11-27',
    ]);

    expect($pastStoppage->idle_days)->toBe(10)
        ->and($todayStoppage->idle_days)->toBe(0)
        ->and($futureStoppage->idle_days)->toBe(0);
});

it('computes idle_duration_days and license expiry on UnassignedDriver accurately', function (): void {
    $driver = new UnassignedDriver([
        'unassigned_since' => '2026-11-05',
        'license_expiry' => '2026-11-10',
    ]);
    $validDriver = new UnassignedDriver([
        'unassigned_since' => '2026-11-25',
        'license_expiry' => '2027-01-15',
    ]);

    expect($driver->idle_duration_days)->toBe(17)
        ->and($driver->is_license_expired)->toBeTrue()
        ->and($validDriver->idle_duration_days)->toBe(0)
        ->and($validDriver->is_license_expired)->toBeFalse();
});

it('computes days_remaining on RegulatoryDocument for future, today, expired, and missing dates', function (): void {
    $validDoc = new RegulatoryDocument(['expiry_date' => '2026-12-07']);
    $todayDoc = new RegulatoryDocument(['expiry_date' => '2026-11-22']);
    $expiredDoc = new RegulatoryDocument(['expiry_date' => '2026-11-12']);
    $missingDoc = new RegulatoryDocument(['expiry_date' => null]);

    expect($validDoc->days_remaining)->toBe(15)
        ->and($todayDoc->days_remaining)->toBe(0)
        ->and($expiredDoc->days_remaining)->toBe(-10)
        ->and($missingDoc->days_remaining)->toBeNull();
});

it('computes delay_days on OwnershipTransfer and downtime_days on EquipmentBreakdown', function (): void {
    $delayedTransfer = new OwnershipTransfer([
        'expected_date' => '2026-11-10',
        'actual_date' => null,
        'status' => OwnershipTransferStatus::Delayed,
    ]);
    $completedLateTransfer = new OwnershipTransfer([
        'expected_date' => '2026-11-10',
        'actual_date' => '2026-11-14',
        'status' => OwnershipTransferStatus::OnTime,
    ]);
    $breakdown = new EquipmentBreakdown([
        'breakdown_date' => '2026-11-15',
    ]);

    expect($delayedTransfer->delay_days)->toBe(12)
        ->and($delayedTransfer->is_overdue)->toBeTrue()
        ->and($completedLateTransfer->delay_days)->toBe(4)
        ->and($breakdown->downtime_days)->toBe(7);
});
