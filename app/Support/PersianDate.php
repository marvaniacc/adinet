<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Minimal Gregorian -> Jalali (Solar Hijri) formatter.
 * Implements the standard 33-year cycle algorithm used across
 * Iranian software; no external dependency required.
 */
final class PersianDate
{
    private const MONTHS = [
        'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
    ];

    public static function format(CarbonInterface|string|null $date, bool $withTime = false): string
    {
        if ($date === null) {
            return '';
        }

        $carbon = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        [$gy, $gm, $gd] = [$carbon->year, $carbon->month, $carbon->day];
        [$jy, $jm, $jd] = self::toJalali($gy, $gm, $gd);

        $out = self::digits(sprintf('%d %s %d', $jd, self::MONTHS[$jm - 1], $jy));

        if ($withTime) {
            $out .= ' - '.self::digits($carbon->format('H:i'));
        }

        return $out;
    }

    /** Convert Latin digits to Persian digits. */
    public static function digits(string|int $value): string
    {
        return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

    /**
     * @return array{int, int, int} [year, month, day] in Jalali calendar
     */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        // Cumulative days elapsed before each Gregorian month.
        $gDayOffsets = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

        $gy2 = $gm > 2 ? $gy + 1 : $gy;

        $days =
            355666
            + 365 * $gy
            + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400)
            + $gd
            + $gDayOffsets[$gm - 1];

        $jy = -1595 + 33 * intdiv($days, 12053);
        $days %= 12053;

        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + $days % 31;
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + ($days - 186) % 30;
        }

        return [$jy, $jm, $jd];
    }
}
