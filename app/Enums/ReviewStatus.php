<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار بررسی',
            self::Approved => 'تأییدشده',
            self::Rejected => 'ردشده',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Approved => 'bg-green-50 text-green-700 ring-green-200',
            self::Rejected => 'bg-red-50 text-red-700 ring-red-200',
        };
    }
}
