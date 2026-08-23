<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">درخواست‌های مشاوره</h1>
            <p class="mt-1 text-sm text-gray-500">درخواست‌های ارسالی موکلان را بررسی و پاسخ دهید.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif
    @error('scheduled_at') <div class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">{{ $message }}</div> @enderror

    {{-- Tabs --}}
    <div class="mt-5 flex flex-wrap gap-2" role="group" aria-label="فیلتر وضعیت درخواست‌ها">
        @php($tabs = [
            'pending' => ['در انتظار پاسخ', $pendingCount],
            'accepted' => ['پذیرفته‌شده', null],
            'closed' => ['بسته‌شده', null],
        ])
        @foreach ($tabs as $key => [$label, $count])
            <button type="button" wire:click="$set('tab', '{{ $key }}')" wire:key="tab-{{ $key }}"
                    aria-pressed="{{ $tab === $key ? 'true' : 'false' }}"
                    class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1 {{ $tab === $key ? 'border-brand-600 bg-brand-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50' }}">
                {{ $label }}
                @if ($count !== null)
                    <span class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] leading-4 {{ $tab === $key ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' }}">{{ \App\Support\PersianDate::digits($count) }}</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Requests --}}
    <div class="mt-6 space-y-3 pb-8">
        @forelse ($requests as $item)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" wire:key="cr-{{ $item->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-gray-900">{{ $item->client->fullName() }}</span>
                            <span dir="ltr" class="text-xs text-gray-400">{{ $item->client->mobile }}</span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $item->status->color() }}">
                                {{ $item->status->label() }}
                            </span>
                        </div>

                        <p class="mt-2 text-sm font-medium text-gray-800">{{ $item->subject }}</p>

                        <p class="mt-2 whitespace-pre-line rounded-xl bg-gray-50 px-4 py-3 text-sm leading-relaxed text-gray-600">{{ \Illuminate\Support\Str::limit($item->description, 400) }}</p>

                        <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-400">
                            @if ($item->service)
                                <span>{{ $item->service->title }}</span>
                            @endif
                            <span>ثبت: {{ \App\Support\PersianDate::format($item->created_at) }}</span>
                            @if ($item->preferred_date)
                                <span>پیشنهاد موکل: {{ \App\Support\PersianDate::format($item->preferred_date) }} {{ $item->preferred_time ?: '' }}</span>
                            @endif
                        </p>

                        @if ($item->status === \App\Enums\ConsultationRequestStatus::Accepted && $item->appointment)
                            <p class="mt-2 inline-flex items-center gap-1 rounded-lg bg-green-50 px-3 py-1.5 text-xs font-medium text-green-700">
                                <span class="material-symbols-rounded text-sm">event</span>
                                نوبت: {{ \App\Support\PersianDate::format($item->appointment->scheduled_at, withTime: true) }}
                            </p>
                        @endif

                        @if ($item->conversation && $item->status !== \App\Enums\ConsultationRequestStatus::Pending)
                            <a href="{{ route('dashboard.lawyer.messages.show', $item->conversation) }}"
                               class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700">
                                <span class="material-symbols-rounded text-sm">forum</span>
                                گفتگو با موکل
                            </a>
                        @endif
                    </div>

                    @if ($item->status === \App\Enums\ConsultationRequestStatus::Pending)
                        <div class="flex shrink-0 gap-2">
                            <button type="button" wire:click="openAccept({{ $item->id }})"
                                    class="rounded-lg bg-green-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-green-700">پذیرش</button>
                            <button type="button" wire:click="openReject({{ $item->id }})"
                                    class="rounded-lg bg-red-50 px-3.5 py-2 text-xs font-semibold text-red-600 hover:bg-red-100">رد</button>
                        </div>
                    @endif
                </div>

                {{-- Accept panel --}}
                @if ($acceptingId === $item->id)
                    <form wire:submit="accept({{ $item->id }})" class="mt-4 rounded-xl border border-green-200 bg-green-50/60 p-4" x-data x-init="$el.scrollIntoView({behavior:'smooth', block:'nearest'})">
                        <h4 class="text-sm font-semibold text-green-800">تعیین نوبت مشاوره</h4>
                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-700">تاریخ (شمسی) *</label>
                                <input type="text" dir="ltr" class="input text-right" wire:model="accept_date_jalali" placeholder="1405/06/25">
                                @error('accept_date_jalali') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                @error('scheduled_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-700">ساعت *</label>
                                <input type="time" dir="ltr" class="input text-right" wire:model="accept_time">
                                @error('accept_time') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-700">یادداشت برای موکل</label>
                                <input type="text" class="input" wire:model="accept_notes" placeholder="اختیاری">
                            </div>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <button type="submit" class="rounded-lg bg-green-600 px-4 py-2 text-xs font-semibold text-white hover:bg-green-700" wire:loading.attr="disabled">تأیید پذیرش</button>
                            <button type="button" wire:click="cancelPanels" class="rounded-lg bg-white px-4 py-2 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50">انصراف</button>
                        </div>
                    </form>
                @endif

                {{-- Reject panel --}}
                @if ($rejectingId === $item->id)
                    <form wire:submit="reject({{ $item->id }})" class="mt-4 rounded-xl border border-red-200 bg-red-50/60 p-4">
                        <label class="mb-1.5 block text-xs font-medium text-red-800">دلیل رد (برای موکل نمایش داده می‌شود)</label>
                        <textarea rows="2" class="input !border-red-200" wire:model="rejection_reason"
                                  placeholder="مثلاً: در حال حاضر ظرفیت پذیرش موکل جدید ندارم."></textarea>
                        @error('rejection_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <div class="mt-3 flex gap-2">
                            <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">ثبت رد</button>
                            <button type="button" wire:click="cancelPanels" class="rounded-lg bg-white px-4 py-2 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50">انصراف</button>
                        </div>
                    </form>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-14 text-center">
                <span class="material-symbols-rounded mx-auto block text-4xl text-gray-300">inbox</span>
                <p class="mt-3 font-medium text-gray-900">درخواستی در این وضعیت نیست</p>
                <p class="mt-1 text-sm text-gray-500">درخواست‌های جدید موکلان پس از تأیید پروفایل شما اینجا نمایش داده می‌شود.</p>
            </div>
        @endforelse
    </div>

    {{ $requests->links() }}
</div>
