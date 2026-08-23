<div>
    <h1 class="text-2xl font-bold text-gray-900">تیکت‌ها</h1>
    <p class="mt-1 text-sm text-gray-500">تیکت‌های ارسالی کاربران (وکلا و موکلان).</p>

    <div class="mt-5 grid gap-3 sm:grid-cols-[1fr_200px]">
        <input type="text" class="input" wire:model.live.debounce.400ms="search"
               placeholder="جستجو: موضوع یا شماره موبایل کاربر…">
        <select class="input" wire:model.live="status">
            <option value="">همه وضعیت‌ها</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-5 space-y-3 pb-8">
        @forelse ($tickets as $ticket)
            <a href="{{ route('admin.tickets.show', ['ticketId' => $ticket->id]) }}"
               class="block rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow-md"
               wire:key="tk-{{ $ticket->id }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="font-semibold text-gray-900">{{ $ticket->subject }}</span>
                    <span class="badge {{ $ticket->status->color() }}">{{ $ticket->status->label() }}</span>
                </div>
                <p class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-400">
                    <span>{{ $ticket->user?->fullName() }}</span>
                    <span dir="ltr">{{ $ticket->user?->mobile }}</span>
                    <span>{{ \App\Support\TicketCategoryLabel::fromValue($ticket->category) }}</span>
                    <span>{{ \App\Support\PersianDate::format($ticket->last_reply_at ?? $ticket->created_at) }}</span>
                </p>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-14 text-center text-gray-400">
                تیکتی یافت نشد.
            </div>
        @endforelse
    </div>

    {{ $tickets->links() }}
</div>
