<?php

declare(strict_types=1);

use App\Enums\AuditEvent;
use App\Enums\OwnershipTransferStatus;
use App\Models\AuditLog;
use App\Models\OwnershipTransfer;
use App\Models\User;

it('automatically records created, updated, deleted, and restored audit logs with user and ip', function (): void {
    $user = User::factory()->operationsManager()->create();
    $this->actingAs($user);
    request()->server->set('REMOTE_ADDR', '10.20.30.40');

    // Created
    $transfer = OwnershipTransfer::factory()->pending()->create([
        'equipment_code' => 'EQ-AUD-101',
        'blocker_reason' => null,
    ]);

    $createdLog = $transfer->auditLogs()->where('event', AuditEvent::Created)->first();

    expect($createdLog)->not->toBeNull()
        ->and($createdLog->user_id)->toBe($user->id)
        ->and($createdLog->auditable_type)->toBe(OwnershipTransfer::class)
        ->and($createdLog->auditable_id)->toBe($transfer->id)
        ->and($createdLog->old_values)->toBeNull()
        ->and($createdLog->new_values['equipment_code'])->toBe('EQ-AUD-101')
        ->and($createdLog->ip_address)->toBe('10.20.30.40');

    // Updated
    $transfer->update([
        'status' => OwnershipTransferStatus::Delayed,
        'blocker_reason' => 'مخالفة مرورية معلقة',
    ]);

    $updatedLog = $transfer->auditLogs()->where('event', AuditEvent::Updated)->first();

    expect($updatedLog)->not->toBeNull()
        ->and($updatedLog->user_id)->toBe($user->id)
        ->and($updatedLog->old_values)->toBe([
            'status' => OwnershipTransferStatus::Pending->value,
            'blocker_reason' => null,
        ])
        ->and($updatedLog->new_values)->toBe([
            'status' => OwnershipTransferStatus::Delayed->value,
            'blocker_reason' => 'مخالفة مرورية معلقة',
        ]);

    // Soft-deleted
    $transfer->delete();

    $deletedLog = $transfer->auditLogs()->where('event', AuditEvent::Deleted)->first();

    expect($deletedLog)->not->toBeNull()
        ->and($deletedLog->old_values['equipment_code'])->toBe('EQ-AUD-101')
        ->and($deletedLog->new_values)->toBeNull();

    // Restored (should log restored without adding spurious updated audit entries)
    $updatedCountBeforeRestore = $transfer->auditLogs()->where('event', AuditEvent::Updated)->count();
    $transfer->restore();

    $restoredLog = $transfer->auditLogs()->where('event', AuditEvent::Restored)->first();

    expect($restoredLog)->not->toBeNull()
        ->and($restoredLog->new_values)->toBe(['deleted_at' => null])
        ->and($transfer->auditLogs()->where('event', AuditEvent::Updated)->count())->toBe($updatedCountBeforeRestore);
});

it('can temporarily suppress audit logging via AuditLog::withoutRecording', function (): void {
    AuditLog::withoutRecording(function (): void {
        OwnershipTransfer::factory()->create();
    });

    expect(AuditLog::query()->count())->toBe(0);

    OwnershipTransfer::factory()->create();

    expect(AuditLog::query()->count())->toBe(1);
});
