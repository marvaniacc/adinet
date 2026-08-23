<?php

namespace App\Console\Commands;

use App\Contracts\OtpSender;
use Illuminate\Console\Command;
use Throwable;

class TestSms extends Command
{
    protected $signature = 'adinet:test-sms {mobile : Iranian mobile to receive the test code}';

    protected $description = 'Send a real OTP through the configured SMS driver and print the raw result';

    public function handle(OtpSender $otpSender): int
    {
        $mobile = preg_replace('/\s+/', '', (string) $this->argument('mobile'));

        $this->info('Driver: '.config('services.sms.driver')
            .' | mode: '.(config('app.env') === 'production' && config('otp.dev_mode') ? 'DEV-MODE (codes are logged, not sent!)' : 'real delivery'));

        try {
            $otpSender->sendOtp($mobile, '123456');
            $this->components->success("OTP delivered to {$mobile}. If it did not arrive, check your Kavenegar panel (template approval / sender line / credit).");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->components->error('Delivery failed: '.$e->getMessage());
            $this->newLine();
            $this->line('Common Kavenegar fixes:');
            $this->bullet('412 «ارسال کننده نامعتبر» → set KAVENEGAR_SENDER to your approved line, or use OTP template mode.');
            $this->bullet('426 «سرویس پیشرفته» → enable the advanced service in the Kavenegar panel, then approve an OTP template named per KAVENEGAR_OTP_TEMPLATE containing: کد ورود: %token');

            return self::FAILURE;
        }
    }

    private function bullet(string $text): void
    {
        $this->line('  • '.$text);
    }
}
