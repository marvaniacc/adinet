<div>
    <h1 class="text-2xl font-bold text-gray-900">پنل وکیل</h1>
    <p class="mt-1 text-sm text-gray-500">سلام {{ auth()->user()->fullName() }}؛ وضعیت حساب حرفه‌ای خود را از این بخش دنبال کنید.</p>

    {{-- Verification status card --}}
    <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-3xl {{ $profile->isVerified() ? 'text-green-600' : 'text-gray-400' }}">
                    {{ $profile->isVerified() ? 'verified' : 'workspace_premium' }}
                </span>
                <div>
                    <h2 class="font-semibold text-gray-900">وضعیت پروفایل</h2>
                    <p class="text-xs text-gray-500">{{ $profile->display_name }}</p>
                </div>
            </div>

            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset {{ $profile->status->color() }}">
                {{ $profile->status->label() }}
            </span>
        </div>

        @if ($profile->status === \App\Enums\LawyerStatus::Rejected && $profile->rejection_reason)
            <div class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm leading-relaxed text-red-700">
                <strong>دلیل رد:</strong> {{ $profile->rejection_reason }}
            </div>
        @endif

        <div class="mt-5 flex flex-wrap gap-2">
            <a href="{{ route('dashboard.lawyer.profile') }}" class="btn-primary !py-2 !text-xs">
                {{ $profile->canSubmitForReview() ? 'تکمیل و ارسال پروفایل' : 'مشاهده/ویرایش پروفایل' }}
            </a>
            <a href="{{ route('dashboard.lawyer.services') }}" class="btn-secondary !py-2 !text-xs">مدیریت خدمات</a>
            @if ($profile->isVerified())
                <a href="{{ route('lawyers.show', $profile->slug) }}" target="_blank" class="btn-secondary !py-2 !text-xs">
                    مشاهده صفحه عمومی ↗
                </a>
            @endif
        </div>
    </div>

    {{-- Quick stats / upcoming sections --}}
    <div class="mt-4 grid gap-4 pb-8 sm:grid-cols-2">
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-6">
            <span class="material-symbols-outlined text-2xl text-gray-300">description</span>
            <p class="mt-2 font-medium text-gray-900">درخواست‌های مشاوره</p>
            <p class="mt-1 text-xs leading-relaxed text-gray-400">با راه‌اندازی مرحله بعد، درخواست‌های موکلان اینجا نمایش داده می‌شود.</p>
        </div>
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-6">
            <span class="material-symbols-outlined text-2xl text-gray-300">event</span>
            <p class="mt-2 font-medium text-gray-900">نوبت‌ها</p>
            <p class="mt-1 text-xs leading-relaxed text-gray-400">مدیریت نوبت‌های مشاوره در مرحله بعدی فعال می‌شود.</p>
        </div>
    </div>
</div>
