<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilitySlot extends Model
{
    /**
     * Carbon dayOfWeek values (0=Sunday .. 6=Saturday), ordered for a
     * Persian week starting on Saturday.
     */
    public const WEEKDAYS = [
        6 => 'شنبه',
        0 => 'یکشنبه',
        1 => 'دوشنبه',
        2 => 'سه‌شنبه',
        3 => 'چهارشنبه',
        4 => 'پنجشنبه',
        5 => 'جمعه',
    ];

    protected $fillable = [
        'lawyer_profile_id',
        'weekday',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function lawyerProfile(): BelongsTo
    {
        return $this->belongsTo(LawyerProfile::class);
    }

    /** Ordered weekday list for selects. */
    public static function weekdayOptions(): array
    {
        return self::WEEKDAYS;
    }

    public function weekdayLabel(): string
    {
        return self::WEEKDAYS[$this->weekday] ?? '?';
    }
}
