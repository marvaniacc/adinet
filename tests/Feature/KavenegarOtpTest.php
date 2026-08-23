<?php

use App\Contracts\OtpSender;
use App\Contracts\SmsProvider;
use App\Services\Sms\KavenegarSmsProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function kavenegarProvider(string $template = ''): KavenegarSmsProvider
{
    return new KavenegarSmsProvider(
        apiKey: 'test-api-key',
        sender: null,
        otpTemplate: $template,
    );
}

it('sends OTP through verify/lookup with the configured template', function () {
    Http::fake([
        '*verify/lookup.json' => Http::response(['return' => ['status' => 200, 'message' => 'تأیید شد']]),
    ]);

    kavenegarProvider('adinet-otp')->sendOtp('09123456789', '123456');

    Http::assertSent(function (Request $request) {
        return str_contains($request->url(), 'verify/lookup.json')
            && $request['receptor'] === '09123456789'
            && $request['token'] === '123456'
            && $request['template'] === 'adinet-otp';
    });
});

it('falls back to generic sms when no template is configured', function () {
    config(['services.kavenegar.sender' => null]);

    Http::fake([
        '*sms/send.json' => Http::response(['return' => ['status' => 200, 'message' => 'ok']]),
    ]);

    kavenegarProvider()->sendOtp('09123456789', '654321');

    Http::assertSent(function (Request $request) {
        return str_contains($request->url(), 'sms/send.json')
            && str_contains($request['message'] ?? '', '654321');
    });
});

it('translates kavenegar error envelopes into exceptions with the persian message', function () {
    Http::fake([
        '*verify/lookup.json' => Http::response([
            'return' => ['status' => 426, 'message' => 'استفاده از این متد نیازمند سرویس پیشرفته می باشد'],
        ], 426),
    ]);

    try {
        kavenegarProvider('adinet-otp')->sendOtp('09123456789', '123456');
        $this->fail('Expected RuntimeException');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain('سرویس پیشرفته')
            ->and($e->getMessage())->toContain('426');
    }
});

it('throws a clear error when the api key is missing', function () {
    config(['services.kavenegar.sender' => null]);
    Http::fake();

    (new KavenegarSmsProvider(apiKey: '', otpTemplate: 't'))->sendOtp('09123456789', '123456');
})->throws(RuntimeException::class);

it('binds one shared provider instance behind both contracts in production config', function () {
    config([
        'services.sms.driver' => 'kavenegar',
        'services.kavenegar.key' => 'k',
        'services.kavenegar.otp_template' => 'adinet-otp',
    ]);

    $sms = app(SmsProvider::class);
    $otp = app(OtpSender::class);

    expect($otp)->toBeInstanceOf(KavenegarSmsProvider::class)
        ->and($sms)->toBe($otp); // singleton sharing
});
