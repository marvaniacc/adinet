<?php

namespace Tests\Support;

use App\Contracts\OtpSender;
use App\Contracts\SmsProvider;

class RecordingSmsProvider implements OtpSender, SmsProvider
{
    public array $sent = [];

    public function send(string $mobile, string $message): void
    {
        $this->sent[] = ['mobile' => $mobile, 'message' => $message];
    }

    public function sendOtp(string $mobile, string $code): void
    {
        $this->send($mobile, 'کد ورود شما به آدینت: '.$code);
    }

    public function lastCode(): ?string
    {
        if ($this->sent === []) {
            return null;
        }

        preg_match('/(\d{6})/', $this->sent[array_key_last($this->sent)]['message'], $matches);

        return $matches[1] ?? null;
    }
}
