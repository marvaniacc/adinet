<div>
    <h1 class="text-2xl font-bold text-gray-900">نظرات موکلان</h1>

    <div class="mt-4 flex flex-wrap gap-4">
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs text-gray-400">میانگین امتیاز (نظرات تأییدشده)</p>
            <p class="mt-1 flex items-center gap-1.5 text-xl font-extrabold text-gray-900">
                <span class="material-symbols-outlined text-amber-400" style="font-variation-settings: 'FILL' 1;">star</span>
                {{ \App\Support\PersianDate::digits($avgRating ?: 0) }}
                <span class="text-sm font-normal text-gray-400">از ۵</span>
            </p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs text-gray-400">تعداد نظرات منتشرشده</p>
            <p class="mt-1 text-xl font-extrabold text-gray-900">{{ \App\Support\PersianDate::digits($approvedCount) }}</p>
        </div>
    </div>

    <div class="mt-6 space-y-3 pb-8">
        @forelse ($reviews as $review)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" wire:key="rv-{{ $review->id }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="font-semibold text-gray-900">{{ $review->client->fullName() }}</span>
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
                @if ($review->consultationRequest)
                    <p class="mt-1 text-xs text-gray-400">موضوع مشاوره: {{ $review->consultationRequest->subject }}</p>
                @endif
                @if ($review->comment)
                    <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $review->comment }}</p>
                @endif
                <p class="mt-1.5 text-xs text-gray-400">{{ \App\Support\PersianDate::format($review->created_at) }}</p>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-14 text-center">
                <span class="material-symbols-outlined mx-auto block text-4xl text-gray-300">reviews</span>
                <p class="mt-3 font-medium text-gray-900">هنوز نظری ثبت نشده است</p>
                <p class="mt-1 text-sm text-gray-500">پس از برگزاری مشاوره‌ها، نظرات موکلان اینجا نمایش داده می‌شود.</p>
            </div>
        @endforelse
    </div>

    {{ $reviews->links() }}
</div>
