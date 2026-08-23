<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">وکلا</h1>
            <p class="mt-1 text-sm text-gray-500">همه وکلای ثبت‌نام‌شده در آدینت.</p>
        </div>
        <a href="{{ route('admin.lawyers.verification') }}" class="btn-secondary !py-2 !text-xs">
            <span class="material-symbols-rounded text-base">verified</span> صف تأیید
        </a>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    {{-- Filters --}}
    <div class="mt-5 grid gap-3 sm:grid-cols-[1fr_200px]">
        <input type="text" class="input" wire:model.live.debounce.400ms="search" placeholder="جستجو بر اساس نام یا شماره موبایل…">
        <select class="input" wire:model.live="status">
            <option value="">همه وضعیت‌ها</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500">
                <tr>
                    <th class="px-5 py-3 text-right font-medium">وکیل</th>
                    <th class="px-5 py-3 text-right font-medium">شهر</th>
                    <th class="px-5 py-3 text-right font-medium">سابقه</th>
                    <th class="px-5 py-3 text-right font-medium">خدمات</th>
                    <th class="px-5 py-3 text-center font-medium">وضعیت</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($lawyers as $lawyer)
                    <tr wire:key="lw-{{ $lawyer->id }}" class="{{ ! $lawyer->isVerified() ? 'bg-gray-50/50' : '' }}">
                        <td class="px-5 py-3">
                            <span class="font-medium text-gray-900">{{ $lawyer->display_name }}</span>
                            <span dir="ltr" class="block text-[11px] text-gray-400">{{ $lawyer->user?->mobile }}</span>
                        </td>
                        <td class="px-5 py-3 text-gray-600">{{ $lawyer->city?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ \App\Support\PersianDate::digits($lawyer->years_of_experience) }} سال</td>
                        <td class="px-5 py-3 text-gray-600">{{ \App\Support\PersianDate::digits($lawyer->services_count) }}</td>
                        <td class="px-5 py-3 text-center">
                            @if ($lawyer->status === \App\Enums\LawyerStatus::Verified && $lawyer->status !== \App\Enums\LawyerStatus::Suspended)
                                <button type="button" wire:click="suspend({{ $lawyer->id }})"
                                        wire:confirm="این وکیل از نمایش عمومی حذف شود؟"
                                        class="rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 ring-inset {{ $lawyer->status->color() }} hover:opacity-75">
                                    {{ $lawyer->status->label() }}
                                </button>
                            @elseif ($lawyer->status === \App\Enums\LawyerStatus::Suspended)
                                <button type="button" wire:click="reinstate({{ $lawyer->id }})"
                                        class="rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 ring-inset {{ $lawyer->status->color() }} hover:opacity-75">
                                    معلق — فعال‌سازی
                                </button>
                            @else
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 ring-inset {{ $lawyer->status->color() }}">
                                    {{ $lawyer->status->label() }}
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <a href="{{ route('lawyers.show', $lawyer->slug) }}" target="_blank"
                               class="block text-end text-xs text-brand-600 hover:text-brand-700">پروفایل ↗</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400">وکیلی یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $lawyers->links() }}
</div>
