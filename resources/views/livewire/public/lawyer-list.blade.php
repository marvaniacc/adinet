<div class="mx-auto max-w-6xl px-4 py-10">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900">وکلای آدینت</h1>
            <p class="mt-2 text-sm text-gray-500">وکلای تأییدشده بر اساس تخصص و شهر خود را بیابید.</p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-200">
            <span class="material-symbols-outlined text-sm">verified</span>
            همه وکلا تأییدشده هستند
        </span>
    </div>

    {{-- Filters --}}
    <div class="mt-6 grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-3">
        <label class="block">
            <span class="mb-1.5 block text-xs font-medium text-gray-500">شهر</span>
            <select class="input" wire:model.live="city">
                <option value="">همه شهرها</option>
                @foreach ($cities as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="block">
            <span class="mb-1.5 block text-xs font-medium text-gray-500">تخصص</span>
            <select class="input" wire:model.live="specialty">
                <option value="">همه تخصص‌ها</option>
                @foreach ($specialties as $s)
                    <option value="{{ $s->slug }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="block">
            <span class="mb-1.5 block text-xs font-medium text-gray-500">نوع مشاوره</span>
            <select class="input" wire:model.live="type">
                <option value="">همه انواع</option>
                @foreach ($types as $t)
                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                @endforeach
            </select>
        </label>
    </div>

    {{-- Results --}}
    <div class="mt-8 grid gap-4 pb-12 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($lawyers as $lawyer)
            <a href="{{ route('lawyers.show', $lawyer->slug) }}"
               class="group flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow-md"
               wire:key="lawyer-{{ $lawyer->id }}">
                <div class="flex items-center gap-3">
                    @if ($lawyer->profile_photo)
                        <img src="{{ Storage::url($lawyer->profile_photo) }}" class="h-14 w-14 shrink-0 rounded-full object-cover ring-2 ring-brand-100" alt="">
                    @else
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600 ring-2 ring-brand-100">
                            <span class="material-symbols-outlined">person</span>
                        </span>
                    @endif
                    <div class="min-w-0">
                        <h2 class="truncate font-bold text-gray-900 group-hover:text-brand-700">{{ $lawyer->display_name }}</h2>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $lawyer->city?->name ?? '—' }} · {{ $lawyer->years_of_experience }} سال سابقه</p>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach ($lawyer->specialties as $specialty)
                        <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-100">{{ $specialty->name }}</span>
                    @endforeach
                </div>

                <p class="mt-3 line-clamp-2 min-h-10 text-sm leading-relaxed text-gray-500">{{ Str::limit($lawyer->bio, 120) }}</p>

                <div class="mt-auto flex items-center justify-between pt-4 text-sm">
                    <span class="text-gray-400">{{ $lawyer->services_count }} خدمت مشاوره</span>
                    <span class="font-semibold text-brand-600">مشاهده پروفایل ←</span>
                </div>
            </a>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white p-16 text-center">
                <span class="material-symbols-outlined mx-auto block text-5xl text-gray-300">person_search</span>
                <p class="mt-4 font-semibold text-gray-900">وکیلی با این مشخصات یافت نشد</p>
                <p class="mt-1 text-sm text-gray-500">فیلترها را تغییر دهید یا بعداً دوباره مراجعه کنید.</p>
                <button type="button" wire:click="$set('city', '');$set('specialty', '');$set('type', '')" class="btn-secondary mt-5">حذف فیلترها</button>
            </div>
        @endforelse
    </div>

    <div class="pb-12">{{ $lawyers->links() }}</div>
</div>
