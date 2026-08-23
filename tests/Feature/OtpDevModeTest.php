<?php

use App\Contracts\SmsProvider;
use App\Services\Auth\OtpRequestStatus;
use App\Services\Auth\OtpService;
use App\Services\Auth\OtpVerifyStatus;
use Tests\Support\RecordingSmsProvider;

beforeEach(function () {
    $this->sms = new RecordingSmsProvider;
    $this->app->instance(SmsProvider::class, $this->sms);
});

it('returns a deterministic code without dispatching sms when dev mode is on', function () {
    config()->set('otp.dev_mode', true);
    config()->set('otp.dev_code', '123456');

    $service = app(OtpService::class);

    expect($service->request('09123456789'))->toBe(OtpRequestStatus::Sent)
        ->and($service->verify('09123456789', '123456'))->toBe(OtpVerifyStatus::Verified);

    // No SMS may be dispatched in dev mode (no cost, no texting real numbers).
    expect($this->sms->sent)->toBeEmpty();
});

it('accepts the dev code regardless of digit formatting', function () {
    config()->set('otp.dev_mode', true);
    config()->set('otp.dev_code', '123۴۵۶'); // operator typo with persian digit

    $service = app(OtpService::class);
    $service->request('09123456789');

    expect($service->verify('09123456789', '123456'))->toBe(OtpVerifyStatus::Verified);
});

it('never accepts the fixed dev code when dev mode is off', function () {
    config()->set('otp.dev_mode', false);
    config()->set('otp.dev_code', '123456');

    $service = app(OtpService::class);
    $service->request('09123456789');
    $code = $this->sms->lastCode();

    // Random code was generated and sent through the provider.
    expect($code)->not()->toBe('123456')
        ->and($service->verify('09123456789', '123456'))->toBe(OtpVerifyStatus::InvalidCode)
        ->and($service->verify('09123456789', $code))->toBe(OtpVerifyStatus::Verified);
});
