<div>
    <h1 class="text-2xl font-bold text-gray-900">پروفایل من</h1>
    <p class="mt-1 text-sm text-gray-500">اطلاعات حساب موکل خود را مدیریت کنید.</p>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <form wire:submit="save" class="mt-6 max-w-xl space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">شماره موبایل</label>
            <input type="text" dir="ltr" disabled value="{{ $mobile }}"
                   class="input cursor-not-allowed bg-gray-50 text-right text-gray-400">
            <p class="mt-1 text-xs text-gray-400">شماره موبایل شناسه ورود شماست و قابل تغییر نیست.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">نام *</label>
                <input type="text" class="input" wire:model="first_name">
                @error('first_name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">نام خانوادگی *</label>
                <input type="text" class="input" wire:model="last_name">
                @error('last_name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <button type="submit" class="btn-primary" wire:loading.attr="disabled">ذخیره</button>
    </form>
</div>
