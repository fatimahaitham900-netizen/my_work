<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case Exported = 'exported';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'إنشاء',
            self::Updated => 'تعديل',
            self::Deleted => 'حذف',
            self::Restored => 'استعادة',
            self::Exported => 'تصدير',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Created => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
            self::Updated => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-600/20',
            self::Deleted => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
            self::Restored => 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20',
            self::Exported => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
