<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Redirected = 'redirected';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار پرداخت',
            self::Redirected => 'در حال پرداخت',
            self::Paid => 'پرداخت‌شده',
            self::Failed => 'ناموفق',
            self::Cancelled => 'لغوشده',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Paid => 'bg-green-50 text-green-700 ring-green-200',
            self::Pending, self::Redirected => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Failed, self::Cancelled => 'bg-red-50 text-red-700 ring-red-200',
        };
    }
}
