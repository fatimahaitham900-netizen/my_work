<?php

declare(strict_types=1);

namespace App\Enums;

enum UnassignedDriverStatus: string
{
    case Ready = 'ready';
    case OnLeave = 'on_leave';
    case Evaluation = 'evaluation';

    public function label(): string
    {
        return match ($this) {
            self::Ready => 'جاهز للتكليف',
            self::OnLeave => 'في إجازة',
            self::Evaluation => 'تحت التقييم',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Ready => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
            self::OnLeave => 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-600/20',
            self::Evaluation => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-600/20',
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
