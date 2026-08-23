<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP (One-Time Password) Settings
    |--------------------------------------------------------------------------
    |
    | Controls the mobile OTP authentication flow. Codes are stored hashed
    | in the cache and are single-use.
    |
    */

    // Lifetime of a generated code, in seconds.
    'ttl_seconds' => (int) env('OTP_TTL_SECONDS', 120),

    // Failed verification attempts allowed before the code is invalidated.
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),

    // Minimum delay between consecutive OTP sends per mobile number.
    'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),

    // Maximum OTP sends per mobile number per rolling hour (SMS abuse guard).
    'max_sends_per_hour' => (int) env('OTP_MAX_SENDS_PER_HOUR', 5),

    // Development convenience: when enabled, every mobile receives the same
    // deterministic code (dev_code below), no real SMS is dispatched, and the
    // login screen shows the code. NEVER enable in a real production environment.
    'dev_mode' => (bool) env('OTP_DEV_MODE', false),

    // The fixed code used while dev_mode is enabled.
    'dev_code' => (string) env('OTP_DEV_CODE', '123456'),

];
