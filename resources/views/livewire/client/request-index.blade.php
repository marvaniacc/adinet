<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <x-dashboard-header title="درخواست‌های من" subtitle="پیگیری درخواست‌های مشاوره ثبت‌شده شما.">
        <a href="{{ route('lawyers.index') }}" class="btn-primary !py-2 !text-xs"><span class="material-symbols-rounded text-base">add</span> درخواست جدید</a></x-dashboard-header>
    </div>
        <a href="{{ route('lawyers.index') }}" class="btn-primary !py-2 !text-xs">
            <span class="material-symbols-rounded text-base">add</span> درخواست جدید
        </a>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <div class="mt-6 space-y-3 pb-8">
        @forelse ($requests as $item)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" wire:key="cr-{{ $item->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('lawyers.show', $item->lawyerProfile->slug) }}" target="_blank" class="font-semibold text-gray-900 hover:text-brand-700">
                                {{ $item->lawyerProfile->display_name }} ↗
                            </a>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $item->status->color() }}">
                                {{ $item->status->label() }}
                            </span>
                        </div>

                        <p class="mt-1.5 text-sm text-gray-700">{{ $item->subject }}</p>

                        <p class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-400">
                            @if ($item->service)
                                <span>{{ $item->service->title }}</span>
                            @endif
                            <span>ثبت: {{ \App\Support\PersianDate::format($item->created_at) }}</span>
                            @if ($item->preferred_date)
                                <span>زمان پیشنهادی: {{ \App\Support\PersianDate::format($item->preferred_date) }} {{ $item->preferred_time ? '- '.$item->preferred_time : '' }}</span>
                            @endif
                        </p>

                        @if ($item->status === \App\Enums\ConsultationRequestStatus::Rejected && $item->rejection_reason)
                            <p class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs leading-relaxed text-red-600">دلیل رد: {{ $item->rejection_reason }}</p>
                        @endif

                        @if ($item->status === \App\Enums\ConsultationRequestStatus::Accepted && $item->appointment)
                            <a href="{{ route('dashboard.appointments') }}" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700">
                                <span class="material-symbols-rounded text-sm">event</span>
                                نوبت هماهنگ شد: {{ \App\Support\PersianDate::format($item->appointment->scheduled_at, withTime: true) }}
                            </a>
                        @endif

                        @if ($item->conversation)
                            <a href="{{ route('messages.show', $item->conversation) }}"
                               class="ms-2 inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700">
                                <span class="material-symbols-rounded text-sm">forum</span>
                                گفتگو با وکیل
                            </a>
                        @endif
                    </div>

                    @if ($item->status->value === 'pending' || $item->status->value === 'accepted')
                        <button type="button" wire:click="cancel({{ $item->id }})"
                                wire:confirm="این درخواست لغو شود؟ این عمل قابل بازگشت نیست."
                                class="rounded-lg px-3 py-2 text-xs font-medium text-gray-500 hover:bg-red-50 hover:text-red-600">
                            لغو درخواست
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-14 text-center">
                <span class="material-symbols-rounded mx-auto block text-4xl text-gray-300">description</span>
                <p class="mt-3 font-medium text-gray-900">هنوز درخواستی ثبت نکرده‌اید</p>
                <p class="mt-1 text-sm text-gray-500">از میان وکلای تأییدشده، وکیل مناسب خود را انتخاب کنید.</p>
                <a href="{{ route('lawyers.index') }}" class="btn-primary mt-5">مشاهده وکلا</a>
            </div>
        @endforelse
    </div>

    {{ $requests->links() }}
</div>
