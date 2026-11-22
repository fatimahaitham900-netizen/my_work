<?php

declare(strict_types=1);

namespace App\Enums;

enum RegulatoryEntityType: string
{
    case Equipment = 'equipment';
    case Driver = 'driver';

    public function label(): string
    {
        return match ($this) {
            self::Equipment => 'معدة',
            self::Driver => 'سائق',
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
