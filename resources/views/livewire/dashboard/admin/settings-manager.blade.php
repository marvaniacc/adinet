<div>
    <h1 class="text-2xl font-bold text-gray-900">تنظیمات</h1>
    <p class="mt-1 text-sm text-gray-500">پیکربندی کلی پلتفرم.</p>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <form wire:submit="save" class="mt-6 max-w-xl space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">انقضای درخواست‌های بی‌پاسخ (روز)</label>
            <input type="number" min="1" max="90" class="input" wire:model="request_expiry_days">
            <p class="mt-1 text-xs text-gray-400">درخواست‌های بدون پاسخ پس از این تعداد روز به‌صورت خودکار منقضی می‌شوند.</p>
            @error('request_expiry_days') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">شماره پشتیبانی (اختیاری)</label>
            <input type="text" dir="ltr" class="input text-right" wire:model="support_mobile" placeholder="09121234567">
            <p class="mt-1 text-xs text-gray-400">در پیام راهنمای لغو نوبت‌های پرداخت‌شده به موکل نمایش داده می‌شود.</p>
            @error('support_mobile') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn-primary" wire:loading.attr="disabled">ذخیره تنظیمات</button>
    </form>
</div>
