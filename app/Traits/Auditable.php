<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Services\DashboardService;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            /** @var Model&self $model */
            $model->recordAuditEvent(
                AuditEvent::Created,
                null,
                $model->extractAuditableValues($model->getAttributes()),
            );
            DashboardService::clearCache();
        });

        static::updated(function (Model $model): void {
            /** @var Model&self $model */
            $changes = $model->extractAuditableValues($model->getChanges());

            if ($changes === []) {
                DashboardService::clearCache();

                return;
            }

            $rawOriginal = $model->getRawOriginal();
            $oldValues = [];

            foreach (array_keys($changes) as $key) {
                $oldValues[$key] = $model->normalizeAuditValue($rawOriginal[$key] ?? null);
            }

            $model->recordAuditEvent(
                AuditEvent::Updated,
                $oldValues,
                $changes,
            );
            DashboardService::clearCache();
        });

        static::deleted(function (Model $model): void {
            /** @var Model&self $model */
            $model->recordAuditEvent(
                AuditEvent::Deleted,
                $model->extractAuditableValues($model->getRawOriginal()),
                null,
            );
            DashboardService::clearCache();
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(function (Model $model): void {
                /** @var Model&self $model */
                $model->recordAuditEvent(
                    AuditEvent::Restored,
                    ['deleted_at' => $model->normalizeAuditValue($model->getRawOriginal('deleted_at'))],
                    ['deleted_at' => null],
                );
                DashboardService::clearCache();
            });
        }
    }

    /**
     * @return MorphMany<AuditLog, $this>
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }

    /**
     * @return list<string>
     */
    protected function auditExcludedAttributes(): array
    {
        return [
            'password',
            'remember_token',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function extractAuditableValues(array $attributes): array
    {
        $excluded = $this->auditExcludedAttributes();
        $filtered = [];

        foreach ($attributes as $key => $value) {
            if (in_array($key, $excluded, true)) {
                continue;
            }

            $filtered[$key] = $this->normalizeAuditValue($value);
        }

        return $filtered;
    }

    protected function normalizeAuditValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    protected function recordAuditEvent(AuditEvent $event, ?array $oldValues, ?array $newValues): void
    {
        if (! AuditLog::$recordingEnabled) {
            return;
        }

        $ipAddress = app()->bound('request') ? request()->ip() : null;

        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'auditable_type' => $this->getMorphClass(),
            'auditable_id' => $this->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress,
        ]);
    }
}
