<?php

declare(strict_types=1);

namespace App\Enums;

enum DriverStoppageStatus: string
{
    case Waiting = 'waiting';
    case Reassigned = 'reassigned';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'بانتظار الإصلاح',
            self::Reassigned => 'تم إعادة التوجيه',
            self::Resolved => 'تمت المعالجة',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Waiting => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
            self::Reassigned => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-600/20',
            self::Resolved => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
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
