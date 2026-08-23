<?php

use App\Support\JalaliDate;
use App\Support\PersianDate;
use Illuminate\Support\Carbon;

it('round-trips jalali <-> gregorian for known dates', function (string $gregorian, int $jy, int $jm, int $jd) {
    $carbon = Carbon::parse($gregorian);

    // G -> J
    expect(PersianDate::toJalali($carbon->year, $carbon->month, $carbon->day))
        ->toBe([$jy, $jm, $jd]);

    // J -> G
    expect(JalaliDate::toGregorian($jy, $jm, $jd))
        ->toBe([$carbon->year, $carbon->month, $carbon->day]);
})->with([
    'Nowruz 1403' => ['2024-03-20', 1403, 1, 1],
    'End of leap year 1403' => ['2025-03-20', 1403, 12, 30],
    'Nowruz 1404' => ['2025-03-21', 1404, 1, 1],
    'Nowruz 1405' => ['2026-03-21', 1405, 1, 1],
    'Mid year' => ['2026-08-23', 1405, 6, 1],
    'Winter date' => ['2026-01-21', 1404, 11, 1],
    'Century boundary' => ['2020-02-29', 1398, 12, 10],
    'Older date' => ['1979-02-11', 1357, 11, 22],
]);

it('parses jalali input in multiple notations', function (string $input, string $expected) {
    expect(JalaliDate::parse($input)->toDateString())->toBe($expected);
})->with([
    'slash ascii' => ['1405/06/15', '2026-09-06'],
    'dash ascii' => ['1405-06-15', '2026-09-06'],
    'persian digits' => ['۱۴۰۵/۰۶/۱۵', '2026-09-06'],
    'single digit month/day' => ['1405/6/5', '2026-08-27'],
]);

it('rejects malformed or out-of-range jalali dates', function (string $input) {
    JalaliDate::parse($input);
})->throws(InvalidArgumentException::class)->with([
    'garbage' => ['hello'],
    'bad month' => ['1405/13/01'],
    'day overflow non-leap esfand' => ['1404/12/30'],
    'year too old' => ['1200/01/01'],
    'empty-ish' => ['/ /'],
]);

it('accepts day 30 of esfand only in kabiseh years', function () {
    expect(JalaliDate::parse('1403/12/30')->toDateString())->toBe('2025-03-20');
});

it('parses date + time combinations', function () {
    $dt = JalaliDate::parseDateTime('۱۴۰۵/۰۶/۲۰', '14:30');

    expect($dt->format('Y-m-d H:i'))->toBe('2026-09-11 14:30');

    JalaliDate::parseDateTime('1405/06/20', '25:00');
})->throws(InvalidArgumentException::class);
