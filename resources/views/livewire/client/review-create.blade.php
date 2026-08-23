<div>
    <h1 class="text-2xl font-bold text-gray-900">ثبت نظر</h1>
    <p class="mt-1 text-sm text-gray-500">
        مشاوره شما با {{ $request->lawyerProfile->display_name }} به پایان رسید. تجربه خود را ثبت کنید.
    </p>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <form wire:submit="store" class="mt-6 max-w-xl space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">امتیاز *</label>
            <div class="flex gap-2" role="radiogroup" aria-label="امتیاز از ۱ تا ۵">
                @foreach ([5, 4, 3, 2, 1] as $value)
                    <button type="button"
                            wire:click="$set('rating', {{ $value }})"
                            aria-pressed="{{ (int) $rating === $value ? 'true' : 'false' }}"
                            class="flex h-11 w-11 items-center justify-center rounded-full border transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1
                                   {{ (int) $rating >= $value && $rating !== ''
                                       ? 'border-amber-400 bg-amber-50 text-amber-500'
                                       : 'border-gray-200 bg-white text-gray-300 hover:border-amber-200 hover:text-amber-300' }}">
                        <span class="material-symbols-outlined" style="{{ (int) $rating >= $value && $rating !== '' ? 'font-variation-settings: \'FILL\' 1;' : '' }}">star</span>
                    </button>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-gray-400">
                {{ $rating === '' ? 'امتیازی انتخاب نشده' : match ((int) $rating) { 5 => 'عالی', 4 => 'خیلی خوب', 3 => 'متوسط', 2 => 'ضعیف', default => 'بسیار ضعیف' } }}
            </p>
            @error('rating') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">نظر شما</label>
            <textarea rows="4" class="input" wire:model="comment"
                      placeholder="کیفیت مشاوره، برخورد وکیل و نتیجه جلسه چگونه بود؟"></textarea>
            @error('comment') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-gray-400">نظر شما پس از بررسی مدیر آدینت منتشر می‌شود.</p>
        </div>

        <button type="submit" class="btn-primary" wire:loading.attr="disabled">ثبت نظر</button>
    </form>
</div>
