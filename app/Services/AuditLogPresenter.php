<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BreakdownCategory;
use App\Enums\BreakdownSeverity;
use App\Enums\ComplianceStatus;
use App\Enums\DriverStoppageStatus;
use App\Enums\OwnershipTransferStatus;
use App\Enums\RegulatoryEntityType;
use App\Enums\RepairStatus;
use App\Enums\UnassignedDriverStatus;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AuditLogPresenter
{
    /**
     * Format a model's chronological audit logs into Arabic UI-friendly entries.
     *
     * @return list<array{
     *     id: int,
     *     event: string,
     *     event_label: string,
     *     event_badge: string,
     *     user_name: string,
     *     ip_address: string,
     *     created_at: string,
     *     changes: list<array{field: string, old: string, new: string}>
     * }>
     */
    public function forModel(Model $model): array
    {
        if (! method_exists($model, 'auditLogs')) {
            return [];
        }

        /** @var Collection<int, AuditLog> $logs */
        $logs = $model->auditLogs()
            ->with('user:id,name')
            ->limit(25)
            ->get();

        return $logs->map(fn (AuditLog $log): array => [
            'id' => $log->id,
            'event' => $log->event->value,
            'event_label' => $log->event->label(),
            'event_badge' => $log->event->badgeClasses(),
            'user_name' => $log->user?->name ?? 'النظام',
            'ip_address' => $log->ip_address ?? '—',
            'created_at' => $log->created_at?->format('Y-m-d H:i') ?? '—',
            'changes' => $this->formatChanges($log->old_values ?? [], $log->new_values ?? []),
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @return list<array{field: string, old: string, new: string}>
     */
    protected function formatChanges(array $oldValues, array $newValues): array
    {
        $keys = array_values(array_unique([...array_keys($oldValues), ...array_keys($newValues)]));
        $formatted = [];

        foreach ($keys as $key) {
            if ($key === 'id') {
                continue;
            }

            $formatted[] = [
                'field' => $this->fieldLabel((string) $key),
                'old' => $this->formatValue((string) $key, $oldValues[$key] ?? null),
                'new' => $this->formatValue((string) $key, $newValues[$key] ?? null),
            ];
        }

        return $formatted;
    }

    protected function fieldLabel(string $key): string
    {
        $translated = __('validation.attributes.'.$key);

        if (is_string($translated) && $translated !== 'validation.attributes.'.$key) {
            return $translated;
        }

        return $key;
    }

    protected function formatValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'نعم' : 'لا';
        }

        if ($key === 'spare_parts_delay') {
            return ((bool) $value) ? 'نعم' : 'لا';
        }

        if (is_string($value)) {
            $enumLabel = match ($key) {
                'status' => OwnershipTransferStatus::tryFrom($value)?->label()
                    ?? DriverStoppageStatus::tryFrom($value)?->label()
                    ?? UnassignedDriverStatus::tryFrom($value)?->label(),
                'category' => BreakdownCategory::tryFrom($value)?->label(),
                'severity' => BreakdownSeverity::tryFrom($value)?->label(),
                'repair_status' => RepairStatus::tryFrom($value)?->label(),
                'entity_type' => RegulatoryEntityType::tryFrom($value)?->label(),
                'compliance_status' => ComplianceStatus::tryFrom($value)?->label(),
                default => null,
            };

            if ($enumLabel !== null) {
                return $enumLabel;
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2} 00:00:00$/', $value) === 1) {
                return substr($value, 0, 10);
            }

            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '—';
    }
}
