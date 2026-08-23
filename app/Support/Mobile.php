<?php

namespace App\Support;

final class Mobile
{
    /**
     * Persian and Arabic digit characters mapped to ASCII equivalents.
     */
    private const DIGIT_MAP = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    /**
     * Convert Persian/Arabic digit characters to ASCII digits.
     */
    public static function toAsciiDigits(string $value): string
    {
        return strtr($value, self::DIGIT_MAP);
    }

    /**
     * Normalize an Iranian mobile number to the canonical 09xxxxxxxxx format.
     *
     * Accepts 09xxxxxxxxx, +989xxxxxxxxx, 00989xxxxxxxxx, 989xxxxxxxxx,
     * 9xxxxxxxxx, Persian/Arabic digits, and stray separators.
     * Returns null when the input is not a valid Iranian mobile number.
     */
    public static function normalize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $digits = self::toAsciiDigits($input);
        $digits = preg_replace('/[\s\-().+]/', '', $digits);

        if ($digits === null || ! preg_match('/^\d+$/', $digits)) {
            return null;
        }

        $digits = match (true) {
            str_starts_with($digits, '0098') => substr($digits, 4),
            str_starts_with($digits, '98') && strlen($digits) === 12 => substr($digits, 2),
            str_starts_with($digits, '9') && strlen($digits) === 10 => $digits,
            default => $digits,
        };

        if (! str_starts_with($digits, '0')) {
            $digits = '0'.$digits;
        }

        return self::isValid($digits) ? $digits : null;
    }

    public static function isValid(?string $value): bool
    {
        return $value !== null && preg_match('/^09\d{9}$/', $value) === 1;
    }
}
