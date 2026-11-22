<?php

declare(strict_types=1);

namespace App\Enums;

enum BreakdownSeverity: string
{
    case Critical = 'critical';
    case Medium = 'medium';
    case Minor = 'minor';

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'حرج',
            self::Medium => 'متوسط',
            self::Minor => 'بسيط',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Critical => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
            self::Medium => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
            self::Minor => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-600/20',
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
