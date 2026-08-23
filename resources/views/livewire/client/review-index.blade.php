<div>
    <h1 class="text-2xl font-bold text-gray-900">نظرات من</h1>
    <p class="mt-1 text-sm text-gray-500">نظرهایی که برای مشاوره‌های انجام‌شده ثبت کرده‌اید.</p>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <div class="mt-6 space-y-3 pb-8">
        @forelse ($reviews as $item)
            @php($review = $item->review)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" wire:key="rv-{{ $item->id }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <a href="{{ route('lawyers.show', $item->lawyerProfile->slug) }}" target="_blank" class="font-semibold text-gray-900 hover:text-brand-700">
                        {{ $item->lawyerProfile->display_name }} ↗
                    </a>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="inline-flex items-center gap-0.5 text-amber-500">
                            @for ($i = 0; $i < $review->rating; $i++)
                                <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' 1;">star</span>
                            @endfor
                        </span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $review->status->color() }}">
                            {{ $review->status->label() }}
                        </span>
                    </span>
                </div>
                @if ($review->comment)
                    <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $review->comment }}</p>
                @endif
                <p class="mt-1.5 text-xs text-gray-400">ثبت: {{ \App\Support\PersianDate::format($review->created_at) }}</p>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-14 text-center">
                <span class="material-symbols-outlined mx-auto block text-4xl text-gray-300">reviews</span>
                <p class="mt-3 font-medium text-gray-900">هنوز نظری ثبت نکرده‌اید</p>
                <p class="mt-1 text-sm text-gray-500">پس از انجام مشاوره، از صفحه نوبت‌ها می‌توانید نظر خود را ثبت کنید.</p>
                <a href="{{ route('dashboard.appointments') }}" class="btn-secondary mt-5">نوبت‌های من</a>
            </div>
        @endforelse
    </div>

    {{ $reviews->links() }}
</div>
