<?php

namespace App\Services\Sms;

use App\Contracts\OtpSender;
use App\Contracts\SmsProvider;
use Illuminate\Support\Facades\Log;

class LogSmsProvider implements OtpSender, SmsProvider
{
    public function send(string $mobile, string $message): void
    {
        Log::info('SMS (log driver)', ['mobile' => $mobile, 'message' => $message]);
    }

    public function sendOtp(string $mobile, string $code): void
    {
        $this->send($mobile, 'کد ورود شما به آدینت: '.$code);
    }
}
