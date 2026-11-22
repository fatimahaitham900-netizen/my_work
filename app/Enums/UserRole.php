<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case OperationsManager = 'operations_manager';
    case FleetSupervisor = 'fleet_supervisor';
    case ExecutiveViewer = 'executive_viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'مدير النظام',
            self::OperationsManager => 'مدير العمليات',
            self::FleetSupervisor => 'مشرف الأسطول',
            self::ExecutiveViewer => 'قارئ تنفيذي',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Admin => 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20',
            self::OperationsManager => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
            self::FleetSupervisor => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
            self::ExecutiveViewer => 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-600/20',
        };
    }

    public function canManageDeviations(): bool
    {
        return in_array($this, [
            self::Admin,
            self::OperationsManager,
            self::FleetSupervisor,
        ], true);
    }

    public function canDeleteDeviations(): bool
    {
        return in_array($this, [
            self::Admin,
            self::OperationsManager,
        ], true);
    }

    public function canViewAuditLogs(): bool
    {
        return in_array($this, [
            self::Admin,
            self::OperationsManager,
        ], true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
