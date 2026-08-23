<div>
    <h1 class="text-2xl font-bold text-gray-900">موکلان</h1>
    <p class="mt-1 text-sm text-gray-500">حساب‌های موکل ثبت‌شده در آدینت.</p>

    <input type="text" class="input mt-5 max-w-md" wire:model.live.debounce.400ms="search"
           placeholder="جستجو بر اساس نام یا شماره موبایل…">

    <div class="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500">
                <tr>
                    <th class="px-5 py-3 text-right font-medium">موکل</th>
                    <th class="px-5 py-3 text-right font-medium">درخواست‌ها</th>
                    <th class="px-5 py-3 text-right font-medium">نوبت‌ها</th>
                    <th class="px-5 py-3 text-right font-medium">عضویت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($clients as $client)
                    <tr wire:key="cl-{{ $client->id }}">
                        <td class="px-5 py-3">
                            <span class="font-medium text-gray-900">{{ $client->fullName() }}</span>
                            <span dir="ltr" class="block text-[11px] text-gray-400">{{ $client->mobile }}</span>
                        </td>
                        <td class="px-5 py-3 text-gray-600">{{ \App\Support\PersianDate::digits($client->consultation_requests_count) }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ \App\Support\PersianDate::digits($client->appointments_count) }}</td>
                        <td class="px-5 py-3 text-xs text-gray-400">{{ \App\Support\PersianDate::format($client->created_at) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center text-gray-400">موکلی یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $clients->links() }}
</div>
