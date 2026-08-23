<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'هماهنگ‌شده',
            self::Completed => 'برگزارشده',
            self::Cancelled => 'لغوشده',
            self::NoShow => 'بدون حضور',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'bg-green-50 text-green-700 ring-green-200',
            self::Completed => 'bg-brand-50 text-brand-700 ring-brand-200',
            self::Cancelled, self::NoShow => 'bg-red-50 text-red-700 ring-red-200',
        };
    }
}
