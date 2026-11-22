<?php

declare(strict_types=1);

namespace App\Enums;

enum BreakdownCategory: string
{
    case Mechanical = 'mechanical';
    case Electrical = 'electrical';
    case Hydraulic = 'hydraulic';
    case Tires = 'tires';
    case Accident = 'accident';

    public function label(): string
    {
        return match ($this) {
            self::Mechanical => 'ميكانيكي',
            self::Electrical => 'كهربائي',
            self::Hydraulic => 'هيدروليكي',
            self::Tires => 'إطارات',
            self::Accident => 'حادث',
        };
    }

    public function chartColor(): string
    {
        return match ($this) {
            self::Mechanical => '#0f172a',
            self::Electrical => '#f59e0b',
            self::Hydraulic => '#0284c7',
            self::Tires => '#64748b',
            self::Accident => '#e11d48',
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
