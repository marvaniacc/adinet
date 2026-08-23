<div wire:poll.5s="$refresh" x-data="{ composer: false }"
     x-init="$watch('composer', v => { if (!v) return; const el = document.getElementById('msg-scroll'); el && (el.scrollTop = el.scrollHeight); })">

    @php($counterpart = $isClientSide ? $conversation->lawyerProfile->display_name : $conversation->client->fullName())

    {{-- Chat header --}}
    <div class="flex items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 py-3">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ $isClientSide ? route('lawyers.show', $conversation->lawyerProfile->slug) : '#' }}"
               class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                <span class="material-symbols-rounded">person</span>
            </a>
            <div class="min-w-0">
                <p class="truncate font-semibold text-gray-900">{{ $counterpart }}</p>
                <p class="truncate text-xs text-gray-400">{{ \Illuminate\Support\Str::limit($conversation->consultationRequest->subject, 60) }}</p>
            </div>
        </div>
        <span class="badge {{ $conversation->consultationRequest->status->color() }}">{{ $conversation->consultationRequest->status->label() }}</span>
    </div>

    {{-- Messages (chat column) --}}
    <div id="msg-scroll" class="mt-4 space-y-2 overflow-y-auto rounded-2xl border border-gray-200 bg-[#f0f2f5] p-4"
         style="height: calc(100vh - 21rem); min-height: 320px;"
         x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
         x-effect="if (!composer) { $nextTick(() => $el.scrollTo({ top: $el.scrollHeight, behavior: 'smooth' })) }">
        @forelse ($messages as $message)
            @php($mine = $message->sender_id === auth()->id())
            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}" wire:key="msg-{{ $message->id }}">
                <div class="max-w-[78%] shadow-sm {{ $mine
                        ? 'rounded-2xl rounded-tr-sm bg-[#d9fdd3] text-gray-900'
                        : 'rounded-2xl rounded-tl-sm bg-white text-gray-800 ring-1 ring-gray-200' }}">
                    <p class="whitespace-pre-line break-words px-3 pt-2 text-sm leading-relaxed">{{ $message->body }}</p>
                    <p class="flex items-center justify-end gap-1 px-3 pb-1.5 pt-0.5 text-[10px] text-gray-400" dir="ltr">
                        {{ $message->created_at->format('H:i') }}
                        @if ($mine && $message->read_at)
                            <span class="material-symbols-rounded !text-[13px] text-sky-500" style="width:13px;height:13px;">done_all</span>
                        @endif
                    </p>
                </div>
            </div>
        @empty
            <p class="py-16 text-center text-sm text-gray-400">اولین پیام را بفرستید.</p>
        @endforelse
    </div>

    {{-- Composer --}}
    @can('sendMessage', $conversation)
        <form wire:submit="send"
              class="mt-3 flex items-end gap-2 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">
            @error('body') <p class="absolute -top-8 start-4 text-xs text-red-600">{{ $message }}</p> @enderror

            <button type="button" title="پیوست سند"
                    class="flex h-10 w-10 flex-none items-center justify-center rounded-full text-gray-400 transition hover:bg-brand-50 hover:text-brand-600"
                    x-on:click="composer = true; $refs.fileInput.click()">
                <span class="material-symbols-rounded">attach_file</span>
            </button>
            <input type="file" class="hidden" wire:model="file" accept=".pdf,.jpg,.jpeg,.png" x-ref="fileInput"
                   x-on:change="composer = true; $nextTick(() => { const n = document.querySelector('[wire\\:model=file] ~ span, .text-xs.text-gray-500'); })">

            <textarea rows="1" x-data x-init="$el.addEventListener('input', () => { $el.style.height='auto'; $el.style.height=Math.min($el.scrollHeight,120)+'px'; })"
                      class="input max-h-30 flex-1 resize-none" wire:model="body" placeholder="پیام…" maxlength="2000"
                      x-on:keydown.enter.prevent="if (!window.event.shiftKey) { $wire.call('send'); $el.style.height='auto'; }"></textarea>

            <button type="submit" wire:loading.attr="disabled"
                    class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-600 text-white transition hover:bg-brand-700 disabled:opacity-50">
                <span class="material-symbols-rounded">send</span>
            </button>
        </form>

        {{-- Attached file indicator --}}
        <div x-cloak x-show="$wire.file" class="mt-2 flex items-center gap-2 rounded-lg bg-brand-50 px-3 py-2 text-xs text-brand-700">
            <span class="material-symbols-rounded text-base">description</span>
            فایل پیوست‌شده آماده ارسال است.
        </div>
    @else
        <div class="mt-3 rounded-xl border border-dashed border-gray-300 bg-white p-4 text-center text-sm text-gray-400">
            این گفتگو برای پیام جدید باز نیست.
        </div>
    @endcan
</div>

@script
<script>
    // Keep chat scrolled to bottom after every Livewire update.
   Livewire.hook('commit', ({ succeed }) => {
        succeed(() => {
            const el = document.getElementById('msg-scroll');
            if (el) el.scrollTop = el.scrollHeight;
        });
    });
    // initial scroll on load
    window.addEventListener('load', () => {
        const el = document.getElementById('msg-scroll');
        if (el) el.scrollTop = el.scrollHeight;
    });
</script>
@endscript
