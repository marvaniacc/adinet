<?php

namespace App\Enums;

enum ConsultationType: string
{
    case Phone = 'phone';
    case Online = 'online';
    case InPerson = 'in_person';

    public function label(): string
    {
        return match ($this) {
            self::Phone => 'مشاوره تلفنی',
            self::Online => 'مشاوره آنلاین',
            self::InPerson => 'مشاوره حضوری',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
