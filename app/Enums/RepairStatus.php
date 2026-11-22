<?php

declare(strict_types=1);

namespace App\Enums;

enum RepairStatus: string
{
    case InWorkshop = 'in_workshop';
    case WaitingParts = 'waiting_parts';
    case External = 'external';
    case Repaired = 'repaired';

    public function label(): string
    {
        return match ($this) {
            self::InWorkshop => 'في الورشة',
            self::WaitingParts => 'بانتظار قطع غيار',
            self::External => 'ورشة خارجية',
            self::Repaired => 'تم الإصلاح',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::InWorkshop => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-600/20',
            self::WaitingParts => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
            self::External => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
            self::Repaired => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
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
