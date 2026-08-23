<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $isLawyer ? 'اسناد مشاوره‌ها' : 'مدارک من' }}</h1>
            <p class="mt-1 text-sm text-gray-500">
                @if ($isLawyer)
                    اسناد بارگذاری‌شده توسط موکلان در مشاوره‌های شما (دسترسی فقط برای طرفین).
                @else
                    اسناد خصوصی شما؛ فقط شما و وکیلِ هر مشاوره به آن‌ها دسترسی دارند.
                @endif
            </p>
        </div>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    {{-- Upload (clients only) --}}
    @unless ($isLawyer)
        <form wire:submit="upload" class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-gray-900">بارگذاری سند جدید</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-[1fr_auto]">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">فایل (PDF/JPG/PNG تا ۵MB) *</label>
                    <input type="file" class="input file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700"
                           wire:model="file" accept=".pdf,.jpg,.jpeg,.png">
                    @error('file') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">مربوط به درخواست *</label>
                    <select class="input min-w-48" wire:model="requestId">
                        <option value="">— انتخاب درخواست —</option>
                        @foreach ($requests as $req)
                            <option value="{{ $req->id }}">{{ \App\Support\PersianDate::digits($req->id) }} · {{ \Illuminate\Support\Str::limit($req->subject, 40) }}</option>
                        @endforeach
                    </select>
                    @error('requestId') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">بارگذاری</button>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-400">سند به‌صورت خصوصی ذخیره می‌شود و فقط وکیلِ همان مشاوره به آن دسترسی خواهد داشت.</p>
        </form>
    @endunless

    {{-- List --}}
    <div class="mt-6 space-y-3 pb-8">
        @forelse ($documents as $document)
            <div class="card flex flex-wrap items-center justify-between gap-3 p-4" wire:key="doc-{{ $document->id }}">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="material-symbols-rounded flex-none text-2xl {{ str_contains($document->mime_type, 'pdf') ? 'text-red-500' : 'text-blue-500' }}">
                        {{ str_contains($document->mime_type, 'pdf') ? 'picture_as_pdf' : 'image' }}
                    </span>
                    <div class="min-w-0">
                        <a href="{{ route('documents.download', $document) }}" class="block truncate font-medium text-gray-900 hover:text-brand-700">
                            {{ $document->original_name }}
                        </a>
                        <p class="truncate text-xs text-gray-400">
                            {{ \App\Support\Str::limit($document->consultationRequest->subject, 50) }}
                            · {{ $document->sizeLabel() }}
                            · {{ \App\Support\PersianDate::format($document->created_at) }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-none items-center gap-1">
                    <a href="{{ route('documents.download', $document) }}"
                       class="rounded-lg p-2 text-gray-400 hover:bg-brand-50 hover:text-brand-600" title="دانلود">
                        <span class="material-symbols-rounded text-lg">download</span>
                    </a>
                    @if (! $isLawyer || auth()->id() === $document->uploaded_by)
                        <button type="button" wire:click="deleteDocument({{ $document->id }})" wire:confirm="این سند حذف شود؟"
                                class="rounded-lg p-2 text-gray-400 hover:bg-red-50 hover:text-red-600" title="حذف">
                            <span class="material-symbols-rounded text-lg">delete</span>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-14 text-center">
                <span class="material-symbols-rounded mx-auto block text-4xl text-gray-300">folder_shared</span>
                <p class="mt-3 font-medium text-gray-900">{{ $isLawyer ? 'سندی برای مشاوره‌های شما ثبت نشده' : 'هنوز مدرکی بارگذاری نکرده‌اید' }}</p>
            </div>
        @endforelse
    </div>

    {{ $documents->links() }}
</div>
