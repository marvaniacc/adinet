<?php

namespace App\Services\Sms;

use App\Contracts\OtpSender;
use App\Contracts\SmsProvider;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class KavenegarSmsProvider implements OtpSender, SmsProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly ?string $sender = null,
        private readonly ?string $otpTemplate = null,
    ) {}

    /** Generic SMS. Requires an approved sender line on the account. */
    public function send(string $mobile, string $message): void
    {
        $this->ensureConfigured();

        $response = Http::asForm()->timeout(15)->post($this->endpoint('sms/send.json'), array_filter([
            'receptor' => $mobile,
            'message' => $message,
            'sender' => $this->sender,
        ]));

        $this->assertOk($response, 'SMS');
    }

    /**
     * OTP via Kavenegar Verify-Lookup (template "%token").
     * Falls back to generic SMS when no template is configured.
     * Verify-Lookup requires the account's advanced service.
     */
    public function sendOtp(string $mobile, string $code): void
    {
        if ($this->otpTemplate === '') {
            $this->send($mobile, 'کد ورود شما به آدینت: '.$code."\nاین کد را در اختیار دیگران قرار ندهید.");

            return;
        }

        $this->ensureConfigured();

        $response = Http::asForm()->timeout(15)->post($this->endpoint('verify/lookup.json'), [
            'receptor' => $mobile,
            'token' => $code,
            'template' => $this->otpTemplate,
        ]);

        $this->assertOk($response, 'OTP');
    }

    private function endpoint(string $path): string
    {
        return "https://api.kavenegar.com/v1/{$this->apiKey}/{$path}";
    }

    private function ensureConfigured(): void
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('کلید API کاوه‌نگار تنظیم نشده است.');
        }
    }

    private function assertOk(Response $response, string $context): void
    {
        // Kavenegar reports business errors inside the JSON envelope
        // (sometimes alongside 4xx HTTP codes) - prefer its human-readable
        // Persian message over raw HTTP status codes.
        $status = $response->json('return.status');
        $message = (string) $response->json('return.message');

        if ($status !== null && (int) $status !== 200) {
            throw new RuntimeException("[{$context}] {$message} (کد {$status})");
        }

        if ($response->failed()) {
            throw new RuntimeException("Kavenegar {$context} HTTP error: ".$response->status());
        }
    }
}
