<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">پشتیبانی</h1>
            <p class="mt-1 text-sm text-gray-500">تیکت‌های شما به تیم آدینت.</p>
        </div>
        <button type="button" wire:click="openForm" class="btn-primary !py-2 !text-xs">
            <span class="material-symbols-rounded text-base">add</span> تیکت جدید
        </button>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    @if ($showForm)
        <form wire:submit="store" class="mt-6 space-y-4 rounded-2xl border border-brand-200 bg-brand-50/40 p-6 shadow-sm">
            <h2 class="font-semibold text-gray-900">ثبت تیکت جدید</h2>

            <div class="grid gap-4 sm:grid-cols-[1fr_200px]">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">موضوع *</label>
                    <input type="text" class="input" wire:model="subject" placeholder="خلاصه مشکل را بنویسید…">
                    @error('subject') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">دسته‌بندی *</label>
                    <select class="input" wire:model="category">
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">شرح کامل *</label>
                <textarea rows="5" class="input" wire:model="body"
                          placeholder="مشکل را با جزئیات توضیح دهید؛ در صورت نیاز شماره نوبت یا درخواست مرتبط را ذکر کنید."></textarea>
                @error('body') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">ارسال تیکت</button>
                <button type="button" wire:click="closeForm" class="btn-secondary">انصراف</button>
            </div>
        </form>
    @endif

    {{-- List --}}
    <div class="mt-6 space-y-3 pb-8">
        @forelse ($tickets as $ticket)
            <a href="{{ route('tickets.show', ['ticketId' => $ticket->id]) }}"
               class="block rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow-md"
               wire:key="tk-{{ $ticket->id }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="font-semibold text-gray-900">{{ $ticket->subject }}</span>
                    <span class="badge {{ $ticket->status->color() }}">{{ $ticket->status->label() }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-400">
                    {{ \App\Support\TicketCategoryLabel::fromValue($ticket->category) }}
                    · آخرین فعالیت: {{ \App\Support\PersianDate::format($ticket->last_reply_at ?? $ticket->created_at) }}
                </p>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-14 text-center">
                <span class="material-symbols-rounded mx-auto block text-4xl text-gray-300">support_agent</span>
                <p class="mt-3 font-medium text-gray-900">تیکتی ثبت نکرده‌اید</p>
                <p class="mt-1 text-sm text-gray-500">برای مشکلات فنی، مالی یا پشتیبانی عمومی تیکت بزنید.</p>
            </div>
        @endforelse
    </div>

    {{ $tickets->links() }}
</div>
