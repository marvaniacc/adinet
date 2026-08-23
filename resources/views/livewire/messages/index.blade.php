<div>
    <h1 class="text-2xl font-bold text-gray-900">پیام‌ها</h1>
    <p class="mt-1 text-sm text-gray-500">گفتگوهای مربوط به درخواست‌های مشاوره شما.</p>

    <div class="mt-6 space-y-3 pb-8">
        @forelse ($conversations as $conversation)
            @php($cr = $conversation->consultationRequest)
            <a href="{{ auth()->user()->isLawyer() ? route('dashboard.lawyer.messages.show', ['conversationId' => $conversation]) : route('messages.show', ['conversationId' => $conversation]) }}"
               class="block rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow-md"
               wire:key="conv-{{ $conversation->id }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-gray-900">{{ $cr->subject }}</p>
                        <p class="mt-0.5 text-xs text-gray-500">
                            {{ auth()->user()->isLawyer() ? 'موکل: '.$conversation->client->fullName() : 'وکیل: '.$conversation->lawyerProfile->display_name }}
                            · {{ \App\Support\PersianDate::format($conversation->last_message_at ?? $conversation->created_at) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($conversation->unread_count > 0)
                            <span class="inline-flex items-center rounded-full bg-brand-600 px-2 py-0.5 text-[11px] font-bold text-white">{{ \App\Support\PersianDate::digits($conversation->unread_count) }}</span>
                        @endif
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $cr->status->color() }}">{{ $cr->status->label() }}</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-14 text-center">
                <span class="material-symbols-outlined mx-auto block text-4xl text-gray-300">forum</span>
                <p class="mt-3 font-medium text-gray-900">گفتگویی وجود ندارد</p>
                <p class="mt-1 text-sm text-gray-500">با ثبت درخواست مشاوره، گفتگو با وکیل آغاز می‌شود.</p>
            </div>
        @endforelse
    </div>

    {{ $conversations->links() }}
</div>
