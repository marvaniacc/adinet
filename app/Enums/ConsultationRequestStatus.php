<?php

namespace App\Enums;

enum ConsultationRequestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار پاسخ',
            self::Accepted => 'پذیرفته‌شده',
            self::Rejected => 'ردشده',
            self::Cancelled => 'لغوشده توسط موکل',
            self::Completed => 'انجام‌شده',
            self::Expired => 'منقضی‌شده',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Accepted => 'bg-green-50 text-green-700 ring-green-200',
            self::Rejected, self::Cancelled, self::Expired => 'bg-red-50 text-red-700 ring-red-200',
            self::Completed => 'bg-brand-50 text-brand-700 ring-brand-200',
        };
    }
}
