<?php

namespace App\Enums;

enum LawyerStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Verified = 'verified';
    case Suspended = 'suspended';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'پیش‌نویس',
            self::PendingReview => 'در انتظار بررسی',
            self::Verified => 'تأییدشده',
            self::Suspended => 'معلق',
            self::Rejected => 'ردشده',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-100 text-gray-600 ring-gray-200',
            self::PendingReview => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Verified => 'bg-green-50 text-green-700 ring-green-200',
            self::Suspended, self::Rejected => 'bg-red-50 text-red-700 ring-red-200',
        };
    }

    /** Public marketplace visibility. */
    public function isPublic(): bool
    {
        return $this === self::Verified;
    }
}
