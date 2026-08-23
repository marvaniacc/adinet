<div>
    <h1 class="text-2xl font-bold text-gray-900">درخواست‌های مشاوره</h1>
    <p class="mt-1 text-sm text-gray-500">نمای کلی همه درخواست‌های ثبت‌شده در آدینت (فقط خواندنی).</p>

    <div class="mt-5 grid gap-3 sm:grid-cols-[1fr_220px]">
        <input type="text" class="input" wire:model.live.debounce.400ms="search" placeholder="جستجو: موضوع، موبایل موکل یا نام وکیل…">
        <select class="input" wire:model.live="status">
            <option value="">همه وضعیت‌ها</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-5 space-y-3 pb-8">
        @forelse ($requests as $item)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" wire:key="rq-{{ $item->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" wire:click="toggle({{ $item->id }})" class="font-semibold text-gray-900 hover:text-brand-700">
                                {{ $item->subject }}
                            </button>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $item->status->color() }}">
                                {{ $item->status->label() }}
                            </span>
                        </div>
                        <p class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-400">
                            <span>موکل: {{ $item->client?->fullName() }}</span>
                            <span>وکیل: {{ $item->lawyerProfile?->display_name }}</span>
                            <span>{{ \App\Support\PersianDate::format($item->created_at) }}</span>
                        </p>
                    </div>
                </div>

                @if ($expandedId === $item->id)
                    <div class="mt-3 rounded-xl bg-gray-50 px-4 py-3 text-sm leading-relaxed text-gray-600">
                        {{ $item->description }}
                        @if ($item->service)
                            <p class="mt-2 text-xs text-gray-400">خدمت: {{ $item->service->title }}</p>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-14 text-center text-gray-400">درخواستی یافت نشد.</div>
        @endforelse
    </div>

    {{ $requests->links() }}
</div>
