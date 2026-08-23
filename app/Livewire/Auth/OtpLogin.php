<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\Auth\OtpRequestStatus;
use App\Services\Auth\OtpService;
use App\Services\Auth\OtpVerifyStatus;
use App\Support\Mobile;
use App\Support\MobileRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.guest')]
class OtpLogin extends Component
{
    public string $step = 'mobile';

    public string $mobile = '';

    public string $code = '';

    public int $cooldown = 0;

    #[Locked]
    public string $registrationRole = User::ROLE_CLIENT;

    public function mount(string $intent = 'client'): void
    {
        if (! in_array($intent, [User::ROLE_CLIENT, User::ROLE_LAWYER], true)) {
            abort(404);
        }

        $this->registrationRole = $intent;

        if (Auth::check()) {
            $this->redirectIntended(Auth::user()->dashboardUrl(), navigate: true);
        }
    }

    public function sendOtp(OtpService $otp): void
    {
        // Guard against SMS pumping: limit requests per IP regardless of number.
        if (RateLimiter::tooManyAttempts($this->ipKey(), 10)) {
            $this->addError('mobile', 'تعداد درخواست‌ها بیش از حد مجاز است. لطفاً بعداً تلاش کنید.');

            return;
        }

        $this->validate(['mobile' => ['required', 'string', new MobileRule]]);

        // Re-validate against the canonical form to reject malformed input early.
        if (Mobile::normalize($this->mobile) === null) {
            $this->addError('mobile', 'شماره موبایل وارد شده معتبر نیست.');

            return;
        }

        try {
            $status = $otp->request($this->mobile);
        } catch (\RuntimeException $e) {
            // Gateway-level failure (Kavenegar errors, config issues).
            report($e);
            \Log::warning('OTP delivery failed', ['error' => $e->getMessage()]);
            $this->addError('mobile', 'ارسال پیامک با خطا مواجه شد. لطفاً چند لحظه دیگر تلاش کنید.');

            return;
        }

        if ($status === OtpRequestStatus::Sent) {
            RateLimiter::hit($this->ipKey(), 3600);
            $this->switchToCode($otp);

            return;
        }

        if ($status === OtpRequestStatus::Cooldown) {
            $this->addError('mobile', 'برای ارسال مجدد کد، چند لحظه صبر کنید.');

            return;
        }

        $this->addError('mobile', 'تعداد درخواست‌های کد بیش از حد مجاز است. لطفاً یک ساعت دیگر تلاش کنید.');
    }

    private function ipKey(): string
    {
        return 'otp-login-ip:'.(string) request()?->ip();
    }

    private function switchToCode(OtpService $otp): void
    {
        $this->cooldown = $otp->cooldownRemainingSeconds($this->mobile);
        $this->reset('code');
        $this->step = 'code';
    }

    public function verifyOtp(OtpService $otp): void
    {
        $this->validate(['code' => ['required', 'digits:6']]);

        $status = $otp->verify($this->mobile, $this->code);

        if ($status !== OtpVerifyStatus::Verified) {
            $messages = [
                OtpVerifyStatus::InvalidCode->name => 'کد وارد شده صحیح نیست.',
                OtpVerifyStatus::Expired->name => 'کد منقضی شده است. دوباره تلاش کنید.',
                OtpVerifyStatus::Locked->name => 'به دلیل تلاش‌های ناموفق، این کد غیرفعال شد. لطفاً کد جدید دریافت کنید.',
            ];

            $this->addError('code', $messages[$status->name]);

            return;
        }

        $user = User::query()->firstOrCreate(
            ['mobile' => Mobile::normalize($this->mobile)],
            ['role' => $this->registrationRole],
        );

        Auth::login($user, remember: false);
        session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        // Destination resolved server-side from the persisted role.
        $this->redirectIntended($user->dashboardUrl(), navigate: true);
    }

    public function backToMobile(): void
    {
        $this->reset('code');
        $this->step = 'mobile';
    }

    public function devOtpCode(): ?string
    {
        $otp = app(OtpService::class);

        return $otp->devMode() ? $otp->devCodeValue() : null;
    }
}
