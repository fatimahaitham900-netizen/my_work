<?php

declare(strict_types=1);

use App\Models\BreakdownDriverStoppage;
use App\Models\CarBreakdown;
use App\Models\EquipmentBreakdown;
use App\Models\FinancialSupportMonth;
use App\Models\FinancialSupportTransfer;
use App\Models\IdleProjectEquipment;
use App\Models\OwnershipTransfer;
use App\Models\RegulatoryDocument;
use App\Models\SparePartTransfer;
use App\Models\TransportViolation;
use App\Models\UnassignedDriver;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('configures sqlite with wal mode, foreign keys, busy timeout, and normal synchronous', function (): void {
    $sqliteConfig = config('database.connections.sqlite');

    expect($sqliteConfig['journal_mode'])->toBe('WAL')
        ->and($sqliteConfig['foreign_key_constraints'])->toBeTrue()
        ->and($sqliteConfig['busy_timeout'])->toBe(5000)
        ->and($sqliteConfig['synchronous'])->toBe('NORMAL');

    $foreignKeysEnabled = DB::selectOne('PRAGMA foreign_keys');
    expect((int) $foreignKeysEnabled->foreign_keys)->toBe(1);
});

it('creates all required core tables and columns from migrations', function (): void {
    expect(Schema::hasColumns('users', ['id', 'name', 'email', 'role', 'job_title', 'is_active', 'deleted_at']))->toBeTrue();

    expect(Schema::hasColumns('audit_logs', [
        'id', 'user_id', 'auditable_type', 'auditable_id', 'event', 'old_values', 'new_values', 'ip_address',
    ]))->toBeTrue();

    expect(Schema::hasColumns('ownership_transfers', [
        'id', 'equipment_code', 'equipment_type', 'branch', 'chassis_number', 'current_owner', 'target_owner',
        'request_date', 'expected_date', 'actual_date', 'status', 'blocker_reason', 'notes', 'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('equipment_breakdowns', [
        'id', 'equipment_code', 'equipment_name', 'brand', 'workshop_name', 'category', 'severity',
        'breakdown_date', 'workshop_entry_date', 'expected_repair_date', 'fault_description',
        'repair_status', 'workshop_status', 'spare_parts_delay', 'estimated_cost', 'cost_note', 'notes', 'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('breakdown_driver_stoppages', [
        'id', 'driver_name', 'employee_id', 'job_title', 'branch', 'phone', 'equipment_code', 'equipment_type',
        'breakdown_id', 'stoppage_start_date', 'expected_resume_date', 'fault_diagnosis', 'workshop_status',
        'action_taken', 'status', 'notes', 'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('unassigned_drivers', [
        'id', 'driver_name', 'employee_id', 'job_title', 'location', 'license_type', 'license_expiry',
        'unassigned_since', 'status', 'notes', 'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('regulatory_documents', [
        'id', 'entity_type', 'entity_identifier', 'document_type', 'document_number',
        'issue_date', 'expiry_date', 'compliance_status', 'renewal_cost', 'notes', 'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('transport_violations', [
        'id', 'violation_number', 'record_holder', 'violation_date', 'amount', 'department', 'city',
        'violation_description', 'objection_status', 'plate_number', 'equipment_code', 'driver_name',
        'employee_id', 'employee_status', 'liability', 'permit_status', 'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('spare_part_transfers', [
        'id', 'item_description', 'from_equipment', 'to_equipment', 'transfer_date', 'notes', 'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('car_breakdowns', [
        'id', 'car_code', 'car_type', 'plate_number', 'model_year', 'previous_receiver', 'employee_id',
        'department', 'job_title', 'fuel_type', 'branch', 'condition_status', 'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('idle_project_equipment', [
        'id', 'equipment_code', 'equipment_type', 'driver_name', 'employee_id', 'branch',
        'site_location', 'equipment_status', 'total_idle_days', 'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('financial_support_months', [
        'id', 'year', 'month', 'title', 'repair_support_amount', 'custody_amount',
        'not_transferred_amount', 'total_equipment_count', 'repaired_count', 'under_repair_count', 'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('financial_support_transfers', [
        'id', 'support_year', 'support_month', 'equipment_type', 'equipment_code', 'amount',
        'beneficiary_name', 'fault_type', 'branch', 'case_status', 'po_number', 'transfer_stage',
        'transfer_date', 'supply_date', 'repair_date', 'repair_status', 'deleted_at',
    ]))->toBeTrue();
});

it('supports soft deletes across all five core models and enforces nullable breakdown foreign key', function (): void {
    $transfer = OwnershipTransfer::factory()->create();
    $breakdown = EquipmentBreakdown::factory()->create();
    $stoppage = BreakdownDriverStoppage::factory()->forBreakdown($breakdown)->create();
    $unassigned = UnassignedDriver::factory()->create();
    $document = RegulatoryDocument::factory()->create();

    $transfer->delete();
    $unassigned->delete();
    $document->delete();

    expect($transfer->fresh()->trashed())->toBeTrue()
        ->and($unassigned->fresh()->trashed())->toBeTrue()
        ->and($document->fresh()->trashed())->toBeTrue()
        ->and($stoppage->breakdown->is($breakdown))->toBeTrue();

    $breakdown->forceDelete();

    expect($stoppage->fresh()->breakdown_id)->toBeNull();

    $stoppage->delete();
    expect($stoppage->fresh()->trashed())->toBeTrue();
});

it('seeds users and realistic arabic fleet datasets cleanly', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(4)
        ->and(OwnershipTransfer::query()->count())->toBe(17)
        ->and(EquipmentBreakdown::query()->count())->toBe(107)
        ->and(BreakdownDriverStoppage::query()->count())->toBe(17)
        ->and(UnassignedDriver::query()->count())->toBe(5)
        ->and(RegulatoryDocument::query()->count())->toBeGreaterThanOrEqual(6)
        ->and(TransportViolation::query()->count())->toBe(197)
        ->and(SparePartTransfer::query()->count())->toBe(11)
        ->and(CarBreakdown::query()->count())->toBe(14)
        ->and(IdleProjectEquipment::query()->count())->toBe(44)
        ->and(FinancialSupportMonth::query()->count())->toBe(4)
        ->and(FinancialSupportTransfer::query()->count())->toBe(110);
});
