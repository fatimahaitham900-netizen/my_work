<?php

declare(strict_types=1);

namespace App\Enums;

enum ComplianceStatus: string
{
    case Valid = 'valid';
    case ExpiringSoon = 'expiring_soon';
    case Expired = 'expired';
    case Missing = 'missing';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'ساري',
            self::ExpiringSoon => 'قارب على الانتهاء',
            self::Expired => 'منتهي',
            self::Missing => 'مفقود',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Valid => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
            self::ExpiringSoon => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
            self::Expired => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
            self::Missing => 'bg-red-100 text-red-800 ring-1 ring-inset ring-red-700/20',
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
