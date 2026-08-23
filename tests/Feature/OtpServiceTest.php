<?php

use App\Contracts\SmsProvider;
use App\Services\Auth\OtpRequestStatus;
use App\Services\Auth\OtpService;
use App\Services\Auth\OtpVerifyStatus;
use App\Support\Mobile;
use Illuminate\Support\Carbon;
use Tests\Support\RecordingSmsProvider;

beforeEach(function () {
    $this->sms = new RecordingSmsProvider;
    $this->app->instance(SmsProvider::class, $this->sms);
});

it('sends an otp via the sms provider on request', function () {
    $status = app(OtpService::class)->request('09123456789');

    expect($status)->toBe(OtpRequestStatus::Sent)
        ->and($this->sms->sent)->toHaveCount(1)
        ->and($this->sms->sent[0]['mobile'])->toBe('09123456789')
        ->and($this->sms->lastCode())->toMatch('/^\d{6}$/');
});

it('stores the code hashed, never in plaintext', function () {
    app(OtpService::class)->request('09123456789');

    $stored = json_encode(Cache::get('otp:code:09123456789'));

    $code = $this->sms->lastCode();

    expect($code)->not()->toBeNull()
        ->and(str_contains($stored, $code))->toBeFalse()
        ->and(str_contains($stored, 'hash'))->toBeTrue();
});

it('verifies a correct code once and consumes it', function () {
    $service = app(OtpService::class);
    $service->request('09123456789');
    $code = $this->sms->lastCode();

    expect($service->verify('09123456789', $code))->toBe(OtpVerifyStatus::Verified);

    // Single use: replaying the same code must fail.
    expect($service->verify('09123456789', $code))->toBe(OtpVerifyStatus::Expired);
});

it('rejects a wrong code without consuming it', function () {
    $service = app(OtpService::class);
    $service->request('09123456789');

    expect($service->verify('09123456789', '000000'))->toBe(OtpVerifyStatus::InvalidCode);

    $code = $this->sms->lastCode();
    expect($service->verify('09123456789', $code))->toBe(OtpVerifyStatus::Verified);
});

it('locks the code after max failed attempts', function () {
    $service = app(OtpService::class);
    $service->request('09123456789');
    $code = $this->sms->lastCode();

    for ($i = 0; $i < config('otp.max_attempts'); $i++) {
        expect($service->verify('09123456789', '000000'))->toBe(
            $i === config('otp.max_attempts') - 1 ? OtpVerifyStatus::Locked : OtpVerifyStatus::InvalidCode
        );
    }

    // Even the correct code no longer works after lockout.
    expect($service->verify('09123456789', $code))->toBe(OtpVerifyStatus::Expired);
});

it('expires codes after the configured ttl', function () {
    $service = app(OtpService::class);
    $service->request('09123456789');
    $code = $this->sms->lastCode();

    Carbon::setTestNow(Carbon::now()->addSeconds(config('otp.ttl_seconds') + 1));

    expect($service->verify('09123456789', $code))->toBe(OtpVerifyStatus::Expired);

    Carbon::setTestNow();
});

it('enforces resend cooldown between requests', function () {
    $service = app(OtpService::class);

    expect($service->request('09123456789'))->toBe(OtpRequestStatus::Sent)
        ->and($service->request('09123456789'))->toBe(OtpRequestStatus::Cooldown);

    // After the cooldown window elapses a new request succeeds.
    Carbon::setTestNow(Carbon::now()->addSeconds(config('otp.resend_cooldown_seconds') + 1));

    expect($service->request('09123456789'))->toBe(OtpRequestStatus::Sent);

    Carbon::setTestNow();
});

it('limits hourly sends per mobile number', function () {
    $service = app(OtpService::class);

    for ($i = 0; $i < config('otp.max_sends_per_hour'); $i++) {
        Cache::forget('otp:cooldown:09123456789');
        expect($service->request('09123456789'))->toBe(OtpRequestStatus::Sent);
    }

    Cache::forget('otp:cooldown:09123456789');
    expect($service->request('09123456789'))->toBe(OtpRequestStatus::LimitReached);
});

it('accepts any supported format of the same number across request and verify', function () {
    $service = app(OtpService::class);
    $service->request('+989123456789');

    expect(Mobile::normalize('+989123456789'))->toBe('09123456789')
        ->and($service->verify('0912 345 6789', $this->sms->lastCode()))->toBe(OtpVerifyStatus::Verified);
});
