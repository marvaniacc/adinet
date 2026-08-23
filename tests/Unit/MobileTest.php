<?php

use App\Support\Mobile;

it('normalizes standard 09xxxxxxxxx numbers unchanged', function () {
    expect(Mobile::normalize('09123456789'))->toBe('09123456789');
});

it('normalizes +98 country code format', function () {
    expect(Mobile::normalize('+989123456789'))->toBe('09123456789');
});

it('normalizes 0098 international prefix format', function () {
    expect(Mobile::normalize('00989123456789'))->toBe('09123456789');
});

it('normalizes 98 country code without plus', function () {
    expect(Mobile::normalize('989123456789'))->toBe('09123456789');
});

it('normalizes missing leading zero format', function () {
    expect(Mobile::normalize('9123456789'))->toBe('09123456789');
});

it('converts persian digits to ascii', function () {
    expect(Mobile::normalize('۰۹۱۲۳۴۵۶۷۸۹'))->toBe('09123456789');
});

it('converts arabic digits to ascii', function () {
    expect(Mobile::normalize('٠٩١٢٣٤٥٦٧٨٩'))->toBe('09123456789');
});

it('strips separators and whitespace', function () {
    expect(Mobile::normalize('0912-345-6789'))->toBe('09123456789');
    expect(Mobile::normalize('0912 345 6789'))->toBe('09123456789');
    expect(Mobile::normalize('(+98) 912 345 6789'))->toBe('09123456789');
});

it('rejects invalid mobile numbers', function (string $input) {
    expect(Mobile::normalize($input))->toBeNull();
})->with([
    'empty' => '',
    'too short' => '09123',
    'landline' => '02112345678',
    'letters' => '0912abc6789',
    'wrong prefix' => '08123456789',
    'extra digits' => '091234567890',
]);

it('validates canonical numbers with isValid', function () {
    expect(Mobile::isValid('09123456789'))->toBeTrue();
    expect(Mobile::isValid('9123456789'))->toBeFalse();
    expect(Mobile::isValid(null))->toBeFalse();
});
