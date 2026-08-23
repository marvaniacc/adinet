<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Redirected = 'redirected';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case RefundRequested = 'refund_requested';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار پرداخت',
            self::Redirected => 'در حال پرداخت',
            self::Paid => 'پرداخت‌شده',
            self::Failed => 'ناموفق',
            self::Cancelled => 'لغوشده',
            self::RefundRequested => 'در انتظار بازگشت وجه',
            self::Refunded => 'وجه بازگشت داده شد',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Paid => 'bg-green-50 text-green-700 ring-green-200',
            self::Pending, self::Redirected => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Failed, self::Cancelled => 'bg-red-50 text-red-700 ring-red-200',
            self::RefundRequested => 'bg-purple-50 text-purple-700 ring-purple-200',
            self::Refunded => 'bg-blue-50 text-blue-700 ring-blue-200',
        };
    }
}
