<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Answered = 'answered';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'باز',
            self::Answered => 'پاسخ داده شد',
            self::Closed => 'بسته',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Answered => 'bg-green-50 text-green-700 ring-green-200',
            self::Closed => 'bg-gray-100 text-gray-500 ring-gray-200',
        };
    }
}
