<?php

namespace App\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MobileRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Mobile::normalize(is_string($value) ? $value : null) === null) {
            $fail('شماره موبایل وارد شده معتبر نیست.');
        }
    }
}
