<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

#[Fillable([
    'key',
    'title',
    'value',
])]
class DashboardMetric extends Model
{
    use Auditable;

    public const string KEY_REGISTRATION_AMENDMENT = 'registration_amendment_needed';

    public const string KEY_DIESEL_CONTROL = 'diesel_consumption_control';

    public const string KEY_CARS_EXPIRED_INSPECTION = 'cars_expired_periodic_inspection';

    public const string KEY_HIGH_OIL_CONSUMPTION = 'high_oil_consumption_equipment';

    public const string KEY_TOTAL_RENTAL_EQUIPMENT = 'total_project_rental_equipment';

    public const string KEY_EQUIPMENT_INVESTIGATIONS = 'equipment_department_investigations';

    public const string KEY_MONTHLY_CERTIFICATE_DELAYS = 'monthly_payment_certificate_delays';

    public const string KEY_MONTH_12_TARGETS = 'project_targets_month_12';

    public const string KEY_DEPARTMENT_PLAN = 'equipment_department_plan';

    public const string KEY_DIESEL_FUNDING_PROJECTS = 'projects_needing_diesel_funding';

    /**
     * Retrieve a custom dashboard metric value with fallback default.
     */
    public static function getValue(string $key, string $default): string
    {
        if (! Schema::hasTable('dashboard_metrics')) {
            return $default;
        }

        $value = self::query()->where('key', $key)->value('value');

        return $value !== null && trim((string) $value) !== '' ? trim((string) $value) : $default;
    }
}
