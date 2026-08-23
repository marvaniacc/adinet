<div wire:poll.5s="render">
    @php($counterpart = $isClientSide ? $conversation->lawyerProfile->display_name : $conversation->client->fullName())

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">گفتگو با {{ $counterpart }}</h1>
            <p class="mt-1 text-sm text-gray-500">موضوع: {{ $conversation->consultationRequest->subject }}</p>
        </div>
        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $conversation->consultationRequest->status->color() }}">
            {{ $conversation->consultationRequest->status->label() }}
        </span>
    </div>

    {{-- Messages --}}
    <div class="mt-6 space-y-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm max-h-[55vh] overflow-y-auto" id="chat-scroll">
        @forelse ($messages as $message)
            @php($mine = $message->sender_id === auth()->id())
            <div class="flex {{ $mine ? 'justify-start' : 'justify-end' }}" wire:key="msg-{{ $message->id }}">
                <div class="max-w-[80%] rounded-2xl px-4 py-2.5 text-sm leading-relaxed {{ $mine ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-800' }}">
                    <p class="whitespace-pre-line break-words">{{ $message->body }}</p>
                    <p class="mt-1 text-[10px] {{ $mine ? 'text-white/60' : 'text-gray-400' }}">
                        {{ \App\Support\PersianDate::format($message->created_at, withTime: true) }}
                        @if ($mine && $message->read_at) · خوانده‌شده @endif
                    </p>
                </div>
            </div>
        @empty
            <p class="py-10 text-center text-sm text-gray-400">اولین پیام را بفرستید.</p>
        @endforelse
    </div>

    @php($canWrite = auth()->user()->can('sendMessage', $conversation))
    {{-- Compose --}}
    @if (! $canWrite)
        <div class="mt-4 rounded-xl border border-dashed border-gray-300 bg-white p-4 text-center text-sm text-gray-400">
            این گفتگو بسته شده است و امکان ارسال پیام جدید وجود ندارد.
        </div>
    @else
    <form wire:submit="send" class="mt-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        @error('body') <p class="mb-2 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('file') <p class="mb-2 text-sm text-red-600">{{ $message }}</p> @enderror

        <textarea rows="3" class="input" wire:model="body" placeholder="پیام خود را بنویسید..." maxlength="2000"></textarea>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-medium text-gray-500 hover:text-brand-600">
                <span class="material-symbols-rounded text-lg">attach_file</span>
                پیوست سند (PDF/JPG/PNG تا ۵MB)
                <input type="file" class="hidden" wire:model="file" accept=".pdf,.jpg,.jpeg,.png">
            </label>
            @if ($this->file)
                <span class="text-xs text-gray-500">{{ $this->file->getClientOriginalName() }}</span>
            @endif
            <button type="submit" class="btn-primary !py-2 !text-xs" wire:loading.attr="disabled">
                ارسال
            </button>
        </div>
        <div wire:loading wire:target="file" class="mt-2 text-xs text-gray-400">در حال بارگذاری فایل...</div>
    </form>
    @endif

    {{-- Documents --}}
    <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm pb-8">
        <h2 class="font-semibold text-gray-900">اسناد مشاوره</h2>
        <p class="mt-1 text-xs text-gray-400">فقط طرفین این مشاوره به اسناد دسترسی دارند.</p>

        <div class="mt-4 space-y-2">
            @forelse ($documents as $document)
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-gray-100 bg-gray-50/70 px-4 py-3" wire:key="doc-{{ $document->id }}">
                    <div class="min-w-0">
                        <a href="{{ route('documents.download', $document) }}" class="block truncate text-sm font-medium text-brand-700 hover:underline">
                            {{ $document->original_name }}
                        </a>
                        <p class="text-[11px] text-gray-400">
                            {{ \App\Support\PersianDate::format($document->created_at) }} · {{ $document->sizeLabel() }}
                            · بارگذاری توسط {{ match ($document->uploader?->role) { 'client' => 'موکل', 'lawyer' => 'وکیل', default => 'مدیر' } }}
                        </p>
                    </div>
                    <div class="flex items-center gap-1">
                        <a href="{{ route('documents.download', $document) }}" class="rounded-lg p-2 text-gray-400 hover:bg-brand-50 hover:text-brand-600" title="دانلود">
                            <span class="material-symbols-rounded text-lg">download</span>
                        </a>
                        @if (auth()->user()->can('delete', $document))
                            <button type="button" wire:click="deleteDocument({{ $document->id }})" wire:confirm="این سند حذف شود؟"
                                    class="rounded-lg p-2 text-gray-400 hover:bg-red-50 hover:text-red-600" title="حذف">
                                <span class="material-symbols-rounded text-lg">delete</span>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-xs text-gray-400">سندی پیوست نشده است.</p>
            @endforelse
        </div>
    </div>
</div>
