<?php

declare(strict_types=1);

namespace App\Enums;

enum OwnershipTransferStatus: string
{
    case OnTime = 'on_time';
    case Delayed = 'delayed';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::OnTime => 'في الوقت المحدد',
            self::Delayed => 'متأخر',
            self::Pending => 'قيد الإجراء',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::OnTime => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
            self::Delayed => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
            self::Pending => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
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
