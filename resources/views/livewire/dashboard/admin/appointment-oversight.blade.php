<div>
    <h1 class="text-2xl font-bold text-gray-900">نوبت‌ها</h1>
    <p class="mt-1 text-sm text-gray-500">نمای کلی نوبت‌های مشاوره (فقط خواندنی).</p>

    <div class="mt-5 grid gap-3 sm:grid-cols-[1fr_220px]">
        <input type="text" class="input" wire:model.live.debounce.400ms="search" placeholder="جستجو: موبایل موکل یا نام وکیل…">
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
                    <th class="px-5 py-3 text-right font-medium">زمان</th>
                    <th class="px-5 py-3 text-right font-medium">موکل</th>
                    <th class="px-5 py-3 text-right font-medium">وکیل</th>
                    <th class="px-5 py-3 text-right font-medium">وضعیت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($appointments as $appointment)
                    <tr wire:key="ao-{{ $appointment->id }}" class="{{ $appointment->status->value !== 'scheduled' ? 'opacity-60' : '' }}">
                        <td class="px-5 py-3 text-gray-700">{{ \App\Support\PersianDate::format($appointment->scheduled_at, withTime: true) }}</td>
                        <td class="px-5 py-3">
                            <span class="text-gray-900">{{ $appointment->client?->fullName() }}</span>
                            <span dir="ltr" class="block text-[11px] text-gray-400">{{ $appointment->client?->mobile }}</span>
                        </td>
                        <td class="px-5 py-3 text-gray-700">{{ $appointment->lawyerProfile?->display_name }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $appointment->status->color() }}">
                                {{ $appointment->status->label() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center text-gray-400">نوبتی یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $appointments->links() }}
</div>
