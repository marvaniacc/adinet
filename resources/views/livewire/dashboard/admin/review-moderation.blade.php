<div>
    <h1 class="text-2xl font-bold text-gray-900">مدیریت نظرات</h1>
    <p class="mt-1 text-sm text-gray-500">بررسی و انتشار نظرات موکلان درباره مشاوره‌ها.</p>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <div class="mt-5 flex flex-wrap gap-2" role="group" aria-label="فیلتر وضعیت نظرات">
        @foreach ($statuses as $s)
            <button type="button" wire:click="$set('status', '{{ $s->value }}')" wire:key="tab-{{ $s->value }}"
                    aria-pressed="{{ $currentStatus->value === $s->value ? 'true' : 'false' }}"
                    class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1 {{ $currentStatus->value === $s->value ? 'border-brand-600 bg-brand-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50' }}">
                {{ $s->label() }}
                <span class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] leading-4 {{ $currentStatus->value === $s->value ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' }}">{{ \App\Support\PersianDate::digits($counts[$s->value] ?? 0) }}</span>
            </button>
        @endforeach
    </div>

    <div class="mt-6 space-y-3 pb-8">
        @forelse ($reviews as $review)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" wire:key="rv-{{ $review->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-gray-900">{{ $review->client?->fullName() }}</span>
                            <span class="text-xs text-gray-400">درباره</span>
                            <span class="text-sm text-gray-700">{{ $review->lawyerProfile?->display_name }}</span>
                        </div>

                        <span class="mt-1.5 inline-flex items-center gap-0.5 text-amber-500">
                            @for ($i = 0; $i < $review->rating; $i++)
                            @endfor
                            <span class="ms-1 text-xs text-gray-400">{{ \App\Support\PersianDate::format($review->created_at) }}</span>
                        </span>

                        @if ($review->comment)
                            <p class="mt-2 max-w-2xl rounded-xl bg-gray-50 px-4 py-3 text-sm leading-relaxed text-gray-600">{{ $review->comment }}</p>
                        @endif
                    </div>

                    <div class="flex shrink-0 gap-2">
                        @if ($review->status !== \App\Enums\ReviewStatus::Approved)
                            <button type="button" wire:click="decide({{ $review->id }}, 'approve')"
                                    wire:target="decide({{ $review->id }}, 'approve')" wire:loading.attr="disabled"
                                    class="rounded-lg bg-green-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-green-700 disabled:opacity-50">تأیید</button>
                        @endif
                        @if ($review->status !== \App\Enums\ReviewStatus::Rejected)
                            <button type="button" wire:click="decide({{ $review->id }}, 'reject')"
                                    wire:target="decide({{ $review->id }}, 'reject')" wire:loading.attr="disabled"
                                    class="rounded-lg bg-red-50 px-3.5 py-2 text-xs font-semibold text-red-600 hover:bg-red-100 disabled:opacity-50">رد</button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-14 text-center">
                <span class="material-symbols-rounded mx-auto block text-4xl text-gray-300">inbox</span>
                <p class="mt-3 text-sm text-gray-500">نظری در این وضعیت وجود ندارد.</p>
            </div>
        @endforelse
    </div>

    {{ $reviews->links() }}
</div>
