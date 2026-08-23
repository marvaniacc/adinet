<?php

namespace App\Providers;

use App\Contracts\OtpSender;
use App\Contracts\PaymentGateway;
use App\Contracts\SmsProvider;
use App\Services\Payment\FakeGateway;
use App\Services\Payment\ZarinpalGateway;
use App\Services\Sms\KavenegarSmsProvider;
use App\Services\Sms\LogSmsProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One shared SMS driver instance serves both contracts.
        $this->app->singleton(SmsProvider::class, function () {
            $driver = (string) config('services.sms.driver', 'log');

            return match ($driver) {
                'kavenegar' => new KavenegarSmsProvider(
                    apiKey: (string) config('services.kavenegar.key'),
                    sender: config('services.kavenegar.sender'),
                    otpTemplate: (string) config('services.kavenegar.otp_template', ''),
                ),
                default => new LogSmsProvider,
            };
        });

        $this->app->bind(OtpSender::class, fn ($app) => $app->make(SmsProvider::class));

        $this->app->bind(PaymentGateway::class, function () {
            return match ((string) config('services.zarinpal.mode', 'fake')) {
                'live', 'sandbox' => new ZarinpalGateway(
                    merchantId: (string) config('services.zarinpal.merchant_id'),
                    mode: (string) config('services.zarinpal.mode'),
                ),
                default => new FakeGateway,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceHttps();
        }
    }
}
