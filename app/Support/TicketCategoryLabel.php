<?php

namespace App\Support;

use App\Models\Ticket;

final class TicketCategoryLabel
{
    public static function fromValue(string $value): string
    {
        return Ticket::CATEGORIES[$value] ?? 'سایر';
    }
}
