<div class="mx-auto max-w-3xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('tickets.index') }}" wire:navigate
               class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                <span class="material-symbols-rounded text-base">arrow_forward</span> بازگشت به تیکت‌ها
            </a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $ticket->subject }}</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="badge {{ $ticket->status->color() }}">{{ $ticket->status->label() }}</span>

            @can('close', $ticket)
                @if ($ticket->status !== \App\Enums\TicketStatus::Closed)
                    <button type="button" wire:click="close" wire:confirm="تیکت بسته شود؟"
                            class="rounded-full border border-gray-300 bg-white px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50">
                        بستن تیکت
                    </button>
                @endif
            @endcan
        </div>
    </div>

    {{-- Thread --}}
    <div class="mt-6 space-y-3">
        @foreach ($messages as $message)
            @php($mine = $message->user_id === auth()->id() || auth()->user()->isAdmin())
            <div class="flex {{ $mine ? 'justify-start' : 'justify-end' }}" wire:key="tm-{{ $message->id }}">
                <div class="max-w-[85%] rounded-2xl px-4 py-3 text-sm leading-relaxed {{ $mine ? 'bg-brand-600 text-white' : 'bg-white ring-1 ring-gray-200 text-gray-800 shadow-sm' }}">
                    <p class="mb-1 text-[11px] font-medium opacity-80">
                        {{ $message->user->fullName() }}
                        · {{ \App\Support\PersianDate::format($message->created_at, withTime: true) }}
                    </p>
                    <p class="whitespace-pre-line break-words">{{ $message->body }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Reply --}}
    @can('reply', $ticket)
        <form wire:submit="reply" class="mt-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            @error('body') <p class="mb-2 text-sm text-red-600">{{ $message }}</p> @enderror

            <textarea rows="3" class="input" wire:model="body"
                      placeholder="پاسخ خود را بنویسید…" maxlength="3000"></textarea>

            <div class="mt-3 flex justify-end">
                <button type="submit" class="btn-primary !py-2 !text-xs" wire:loading.attr="disabled">ارسال پاسخ</button>
            </div>
        </form>
    @else
        <div class="mt-6 rounded-xl border border-dashed border-gray-300 bg-white p-4 text-center text-sm text-gray-400">
            این تیکت بسته شده است؛ برای پیگیری موضوع تیکت جدید ثبت کنید.
        </div>
    @endcan
</div>
