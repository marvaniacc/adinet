<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">گزارشات</h1>
            <p class="mt-1 text-sm text-gray-500">بایگانی خصوصی گزارش‌های توسعه و نگهداری آدینت.</p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
            مجموع: {{ \App\Support\PersianDate::digits($total) }}
        </span>
    </div>

    {{-- Type filter tabs --}}
    <div class="mt-5 flex flex-wrap gap-2" role="group" aria-label="فیلتر نوع گزارش">
        <button type="button" wire:click="$set('type', '')" wire:key="tab-all"
                aria-pressed="{{ ($activeType === null) ? 'true' : 'false' }}"
                class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1 {{ ($activeType === null) ? 'border-brand-600 bg-brand-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50' }}">
            همه
            <span class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] leading-4 {{ ($activeType === null) ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' }}">{{ \App\Support\PersianDate::digits($total) }}</span>
        </button>

        @foreach ($types as $t)
            <button type="button" wire:click="$set('type', '{{ $t->value }}')" wire:key="tab-{{ $t->value }}"
                    aria-pressed="{{ $activeType?->value === $t->value ? 'true' : 'false' }}"
                    class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1 {{ $activeType?->value === $t->value ? 'border-brand-600 bg-brand-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50' }}">
                {{ $t->label() }}
                <span class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] leading-4 {{ $activeType?->value === $t->value ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' }}">{{ \App\Support\PersianDate::digits($counts[$t->value] ?? 0) }}</span>
            </button>
        @endforeach
    </div>

    {{-- List --}}
    <div class="mt-6 space-y-3 pb-8">
        @forelse ($reports as $report)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" wire:key="rp-{{ $report->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold text-gray-900">{{ $report->title }}</h3>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $report->type->color() }}">
                                {{ $report->type->label() }}
                            </span>
                        </div>

                        @if ($report->description)
                            <p class="mt-1.5 max-w-3xl whitespace-pre-line text-sm leading-relaxed text-gray-600">{{ $report->description }}</p>
                        @endif

                        <p class="mt-1.5 text-xs text-gray-400">
                            {{ \App\Support\PersianDate::format($report->created_at) }} · {{ $report->file_name }}
                        </p>
                    </div>

                    <a href="{{ route('admin.reports.download', $report) }}"
                       class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 hover:border-brand-300 hover:text-brand-700">
                        <span class="material-symbols-outlined text-base">download</span>
                        دانلود
                    </a>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-14 text-center">
                <span class="material-symbols-outlined mx-auto block text-4xl text-gray-300">folder_open</span>
                <p class="mt-3 font-medium text-gray-900">گزارشی در این دسته وجود ندارد</p>
                <p class="mt-1 text-sm text-gray-500">گزارش‌ها از طریق دستور adinet:file-report ثبت می‌شوند.</p>
            </div>
        @endforelse
    </div>

    {{ $reports->links() }}
</div>
