<div class="mx-auto max-w-3xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('admin.tickets.index') }}" wire:navigate
               class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                <span class="material-symbols-rounded text-base">arrow_forward</span> بازگشت
            </a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $ticket->subject }}</h1>
            <p class="mt-1 text-xs text-gray-400">
                {{ $ticket->user?->fullName() }} · <span dir="ltr">{{ $ticket->user?->mobile }}</span>
                · {{ \App\Support\TicketCategoryLabel::fromValue($ticket->category) }}
            </p>
        </div>
        <span class="badge {{ $ticket->status->color() }}">{{ $ticket->status->label() }}</span>
    </div>

    {{-- Thread --}}
    <div class="mt-6 space-y-3">
        @foreach ($messages as $message)
            @php($isAdminMsg = $message->user->role === 'admin')
            <div class="flex {{ $isAdminMsg ? 'justify-start' : 'justify-end' }}" wire:key="tm-{{ $message->id }}">
                <div class="max-w-[85%] rounded-2xl px-4 py-3 text-sm leading-relaxed {{ $isAdminMsg ? 'bg-purple-600 text-white' : 'bg-white ring-1 ring-gray-200 text-gray-800 shadow-sm' }}">
                    <p class="mb-1 text-[11px] font-medium opacity-80">
                        {{ $message->user->fullName() }}
                        · {{ \App\Support\PersianDate::format($message->created_at, withTime: true) }}
                    </p>
                    <p class="whitespace-pre-line break-words">{{ $message->body }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Admin reply --}}
    @if ($ticket->status !== \App\Enums\TicketStatus::Closed)
        <form wire:submit="reply" class="mt-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            @error('body') <p class="mb-2 text-sm text-red-600">{{ $message }}</p> @enderror

            <textarea rows="3" class="input" wire:model="body" placeholder="پاسخ پشتیبانی…" maxlength="3000"></textarea>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary !py-2 !text-xs" wire:loading.attr="disabled">ارسال پاسخ</button>
                    <button type="button" wire:click="closeTicket" wire:confirm="تیکت بسته شود؟"
                            class="rounded-full border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50">بستن</button>
                </div>
            </div>
        </form>
    @else
        <div class="mt-6 rounded-xl border border-dashed border-gray-300 bg-white p-4 text-center text-sm text-gray-400">
            این تیکت بسته شده است.
            <button type="button" wire:click="reopen" class="ms-2 font-medium text-brand-600 hover:underline">بازگشایی</button>
        </div>
    @endif
</div>
