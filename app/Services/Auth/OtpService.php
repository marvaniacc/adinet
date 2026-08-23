<?php

namespace App\Services\Auth;

use App\Contracts\OtpSender;
use App\Support\Mobile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;

class OtpService
{
    public function __construct(
        private readonly OtpSender $otpSender,
    ) {}

    /**
     * Generate and deliver an OTP for the given mobile number.
     * Input may be any supported Iranian format; it is normalized first.
     */
    public function request(string $rawMobile): OtpRequestStatus
    {
        $mobile = Mobile::normalize($rawMobile);

        if ($mobile === null) {
            throw new \InvalidArgumentException('Invalid Iranian mobile number.');
        }

        if (RateLimiter::tooManyAttempts($this->sendKey($mobile), $this->maxSendsPerHour())) {
            return OtpRequestStatus::LimitReached;
        }

        if (Cache::has($this->cooldownKey($mobile))) {
            return OtpRequestStatus::Cooldown;
        }

        // Dev mode: deterministic code, no real SMS dispatch.
        // All rate limits and storage stay identical to production.
        if ($this->devMode()) {
            $code = $this->devCode();
        } else {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        }

        Cache::put($this->storageKey($mobile), [
            'hash' => $this->hash($code, $mobile),
            'attempts' => 0,
        ], $this->ttl());

        Cache::put($this->cooldownKey($mobile), $this->now() + $this->resendCooldown(), $this->resendCooldown());
        RateLimiter::hit($this->sendKey($mobile), 3600);

        if ($this->devMode()) {
            logger()->info("OTP (dev mode) for {$mobile}: {$code}");
        } else {
            // Real delivery through the configured provider (Kavenegar).
            $this->otpSender->sendOtp($mobile, $code);
        }

        return OtpRequestStatus::Sent;
    }

    /**
     * Verify a submitted code. A correct code is consumed immediately
     * (single use). Failed attempts accumulate until the code locks.
     */
    public function verify(string $rawMobile, string $code): OtpVerifyStatus
    {
        $mobile = Mobile::normalize($rawMobile);

        if ($mobile === null) {
            return OtpVerifyStatus::Expired;
        }

        $entry = Cache::get($this->storageKey($mobile));

        if (! is_array($entry) || ! isset($entry['hash'])) {
            return OtpVerifyStatus::Expired;
        }

        $submitted = preg_replace('/\D/', '', Mobile::toAsciiDigits($code)) ?? '';

        if ($entry['attempts'] >= $this->maxAttempts()) {
            Cache::forget($this->storageKey($mobile));

            return OtpVerifyStatus::Locked;
        }

        if ($submitted === '' || ! hash_equals($entry['hash'], $this->hash($submitted, $mobile))) {
            $entry['attempts']++;

            if ($entry['attempts'] >= $this->maxAttempts()) {
                Cache::forget($this->storageKey($mobile));

                return OtpVerifyStatus::Locked;
            }

            Cache::put($this->storageKey($mobile), $entry, $this->ttl());

            return OtpVerifyStatus::InvalidCode;
        }

        Cache::forget($this->storageKey($mobile));
        RateLimiter::clear($this->sendKey($mobile));

        return OtpVerifyStatus::Verified;
    }

    public function cooldownRemainingSeconds(string $rawMobile): int
    {
        $mobile = Mobile::normalize($rawMobile);

        if ($mobile === null) {
            return 0;
        }

        $expiresAt = Cache::get($this->cooldownKey($mobile));

        return is_int($expiresAt) ? max(0, $expiresAt - $this->now()) : 0;
    }

    private function now(): int
    {
        return (int) Date::now()->getTimestamp();
    }

    private function hash(string $code, string $mobile): string
    {
        return hash_hmac('sha256', $mobile.'|'.$code, (string) config('app.key'));
    }

    private function storageKey(string $mobile): string
    {
        return "otp:code:{$mobile}";
    }

    private function cooldownKey(string $mobile): string
    {
        return "otp:cooldown:{$mobile}";
    }

    private function sendKey(string $mobile): string
    {
        return "otp:send:{$mobile}";
    }

    private function ttl(): int
    {
        return (int) config('otp.ttl_seconds', 120);
    }

    private function resendCooldown(): int
    {
        return (int) config('otp.resend_cooldown_seconds', 60);
    }

    private function maxAttempts(): int
    {
        return (int) config('otp.max_attempts', 5);
    }

    private function maxSendsPerHour(): int
    {
        return (int) config('otp.max_sends_per_hour', 5);
    }

    public function devMode(): bool
    {
        return (bool) config('otp.dev_mode', false);
    }

    public function devCodeValue(): string
    {
        return $this->devCode();
    }

    private function devCode(): string
    {
        $digits = preg_replace('/\D/', '', Mobile::toAsciiDigits((string) config('otp.dev_code', '123456')));

        return str_pad($digits !== '' ? $digits : '123456', 6, '0', STR_PAD_LEFT);
    }
}
