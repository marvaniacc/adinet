<div>
    @if ($step === 'mobile')
        <h1 class="text-xl font-bold text-gray-900">ورود / ثبت‌نام</h1>

        @if ($registrationRole === \App\Models\User::ROLE_LAWYER)
            <p class="mt-2 rounded-lg bg-brand-50 px-3 py-2 text-sm text-brand-700">
                ثبت‌نام به عنوان وکیل — پس از ورود، پروفایل حرفه‌ای خود را تکمیل کنید.
            </p>
        @endif

        <p class="mt-3 text-sm leading-relaxed text-gray-500">
            برای ورود یا ایجاد حساب کاربری، شماره موبایل خود را وارد کنید. کد تأیید برای شما پیامک خواهد شد.
        </p>

        <form wire:submit="sendOtp" class="mt-6 space-y-4">
            <div>
                <label for="mobile" class="mb-1.5 block text-sm font-medium text-gray-700">شماره موبایل</label>
                <input
                    id="mobile"
                    type="tel"
                    dir="ltr"
                    inputmode="tel"
                    autocomplete="tel"
                    placeholder="09xxxxxxxxx"
                    class="input text-right"
                    wire:model="mobile"
                >
                @error('mobile') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="sendOtp">دریافت کد تأیید</span>
                <span wire:loading wire:target="sendOtp">در حال ارسال...</span>
            </button>
        </form>

        <div class="mt-6 border-t border-gray-100 pt-5 text-center text-sm">
            @if ($registrationRole === \App\Models\User::ROLE_CLIENT)
                <a href="{{ route('lawyer.register') }}" class="text-brand-600 hover:text-brand-700">وکیل هستید؟ ثبت‌نام وکلا</a>
            @else
                <a href="{{ route('login') }}" class="text-brand-600 hover:text-brand-700">موکل هستید؟ ورود موکلان</a>
            @endif
        </div>
    @else
        <h1 class="text-xl font-bold text-gray-900">کد تأیید</h1>
        <p class="mt-3 text-sm leading-relaxed text-gray-500">
            کد ۶ رقمی ارسال‌شده به
            <span dir="ltr" class="font-semibold text-gray-800">{{ $mobile }}</span>
            را وارد کنید.
        </p>

        @if ($this->devOtpCode())
            <div class="mt-4 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800">
                <span class="material-symbols-outlined text-base">science</span>
                حالت توسعه — کد ثابت:
                <span dir="ltr" class="font-bold tracking-widest">{{ $this->devOtpCode() }}</span>
            </div>
        @endif

        <form wire:submit="verifyOtp" class="mt-6 space-y-4">
            <div>
                <label for="code" class="sr-only">کد تأیید</label>
                <input
                    id="code"
                    type="text"
                    dir="ltr"
                    inputmode="numeric"
                    maxlength="6"
                    placeholder="------"
                    class="input text-center text-lg tracking-[0.5em]"
                    wire:model="code"
                    x-init="$nextTick(() => $el.focus())"
                >
                @error('code') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
                تأیید و ورود
            </button>
        </form>

        <div class="mt-6 flex items-center justify-between border-t border-gray-100 pt-5 text-sm">
            <button type="button" wire:click="backToMobile" class="flex items-center gap-1 text-gray-500 hover:text-gray-700">
                <span class="material-symbols-outlined text-base">arrow_forward</span>
                تغییر شماره
            </button>

            <span x-data="{ seconds: {{ $cooldown }} }" x-init="
                    if (seconds > 0) {
                        const t = setInterval(() => { if (--seconds <= 0) clearInterval(t) }, 1000);
                        window.addEventListener('livewire:navigated', () => clearInterval(t));
                    }
                ">
                <template x-if="seconds > 0">
                    <span class="text-gray-400">ارسال مجدد تا <span x-text="seconds"></span> ثانیه</span>
                </template>
                <template x-if="seconds <= 0">
                    <button type="button" wire:click="sendOtp" class="font-medium text-brand-600 hover:text-brand-700">
                        ارسال مجدد کد
                    </button>
                </template>
            </span>
        </div>
    @endif
</div>
