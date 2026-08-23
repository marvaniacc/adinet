<?php

use App\Support\PersianDate;
use Illuminate\Support\Carbon;

it('converts gregorian dates to jalali correctly', function (string $date, string $expected) {
    expect(PersianDate::format(Carbon::parse($date)))->toBe($expected);
})->with([
    'nowruz 1403' => ['2024-03-20', '۱ فروردین ۱۴۰۳'],
    'nowruz 1404' => ['2025-03-21', '۱ فروردین ۱۴۰۴'],
    'nowruz 1405' => ['2026-03-21', '۱ فروردین ۱۴۰۵'],
    'mid year' => ['2026-08-23', '۱ شهریور ۱۴۰۵'],
    // 1403 is a leap year: Esfand has 30 days, Nowruz 1404 lands on Mar 21.
    'end of leap year' => ['2025-03-20', '۳۰ اسفند ۱۴۰۳'],
]);

it('formats date with time', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-23 14:30', 'Asia/Tehran'));

    expect(PersianDate::format(now(), withTime: true))->toContain('شهریور')
        ->and(PersianDate::format(now(), withTime: true))->toContain(':');
});

it('converts latin digits to persian', function () {
    expect(PersianDate::digits(12345))->toBe('۱۲۳۴۵')
        ->and(PersianDate::digits('0'))->toBe('۰');
});

it('returns empty string for null dates', function () {
    expect(PersianDate::format(null))->toBe('');
});
