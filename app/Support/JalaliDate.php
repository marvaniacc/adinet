<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Jalali (Solar Hijri) <-> Gregorian conversion and parsing.
 * All display formatting lives in PersianDate; this class handles
 * the computational side so forms can accept Persian dates natively.
 */
final class JalaliDate
{
    /**
     * Parse a user-supplied Jalali date into a Carbon date (time 00:00).
     *
     * Accepts: 1405/06/15, ۱۴۰۵-۰۶-۱۵, 1405-6-15, leading/t trailing spaces.
     *
     * @throws InvalidArgumentException on malformed or out-of-range input
     */
    public static function parse(string $value): Carbon
    {
        $ascii = Mobile::toAsciiDigits(trim($value));
        $normalized = str_replace(['-', '.', ' '], '/', $ascii);

        if (! preg_match('/^(\d{3,4})\/(\d{1,2})\/(\d{1,2})$/', $normalized, $m)) {
            throw new InvalidArgumentException('تاریخ شمسی معتبر نیست. نمونه صحیح: ۱۴۰۵/۰۶/۱۵');
        }

        [, $jy, $jm, $jd] = array_map('intval', $m);

        if ($jy < 1300 || $jy > 1500) {
            throw new InvalidArgumentException('سال شمسی باید بین ۱۳۰۰ و ۱۵۰۰ باشد.');
        }

        if ($jm < 1 || $jm > 12) {
            throw new InvalidArgumentException('ماه شمسی باید بین ۱ و ۱۲ باشد.');
        }

        if ($jd < 1 || $jd > self::daysInJalaliMonth($jy, $jm)) {
            throw new InvalidArgumentException('روز ماه برای این ماه شمسی معتبر نیست.');
        }

        [$gy, $gm, $gd] = self::toGregorian($jy, $jm, $jd);

        // Pure date math - deliberately no container/config dependency.
        return Carbon::createFromDate($gy, $gm, $gd)->startOfDay();
    }

    /** Combine a Jalali date string and an HH:MM time into a Carbon datetime. */
    public static function parseDateTime(string $jalaliDate, string $time): Carbon
    {
        if (! preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', trim($time))) {
            throw new InvalidArgumentException('ساعت معتبر نیست.');
        }

        return self::parse($jalaliDate)->setTimeFromTimeString(trim($time).':00');
    }

    public static function daysInJalaliMonth(int $jy, int $jm): int
    {
        if ($jm <= 6) {
            return 31;
        }

        if ($jm <= 11) {
            return 30;
        }

        // Esfand: 30 in kabiseh (leap) years, 29 otherwise.
        return self::isJalaliLeap($jy) ? 30 : 29;
    }

    /**
     * Birashk-style jalali leap check (accurate for 1178-1634 AP range).
     */
    public static function isJalaliLeap(int $jy): bool
    {
        $mod = $jy % 33;

        return in_array($mod, [1, 5, 9, 13, 17, 22, 26, 30], true);
    }

    /**
     * Inverse of PersianDate::toJalali - standard jdf algorithm.
     *
     * @return array{int, int, int} [year, month, day]
     */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $gy = $jy <= 979 ? 621 : 1600;
        $jy -= $jy <= 979 ? 0 : 979;

        $days =
            365 * $jy
            + intdiv($jy, 33) * 8
            + intdiv(($jy % 33) + 3, 4)
            + 78
            + $jd
            + ($jm < 7 ? ($jm - 1) * 31 : ($jm - 7) * 30 + 186);

        $gy += 400 * intdiv($days, 146097);
        $days %= 146097;

        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;

            if ($days >= 365) {
                $days++;
            }
        }

        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;

        foreach ([31, 0, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31] as $gm => $daysInMonth) {
            if ($gm === 1) {
                $daysInMonth = (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0)) ? 29 : 28;
            }

            if ($gd <= $daysInMonth) {
                break;
            }

            $gd -= $daysInMonth;
        }

        return [$gy, $gm + 1, $gd];
    }

    /** Format a Carbon as ۱۴۰۵/۰۶/۱۵ (sortable, compact). */
    public static function formatShort(CarbonInterface $date): string
    {
        [$jy, $jm, $jd] = PersianDate::toJalali($date->year, $date->month, $date->day);

        return PersianDate::digits(sprintf('%04d/%02d/%02d', $jy, $jm, $jd));
    }
}
