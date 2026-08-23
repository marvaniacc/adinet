<div>
    <h1 class="text-2xl font-bold text-gray-900">فعالیت‌ها</h1>
    <p class="mt-1 text-sm text-gray-500">گزارش رویدادهای مدیریتی (حسابرسی).</p>

    <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500">
                <tr>
                    <th class="px-5 py-3 text-right font-medium">مدیر</th>
                    <th class="px-5 py-3 text-right font-medium">اقدام</th>
                    <th class="px-5 py-3 text-right font-medium">موضوع</th>
                    <th class="px-5 py-3 text-right font-medium">زمان</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($actions as $action)
                    <tr wire:key="aa-{{ $action->id }}">
                        <td class="px-5 py-3">
                            <span class="font-medium text-gray-900">{{ $action->admin?->fullName() }}</span>
                            <span dir="ltr" class="block text-[11px] text-gray-400">{{ $action->admin?->mobile }}</span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-[11px] font-medium text-brand-700 ring-1 ring-inset ring-brand-200" dir="ltr">
                                {{ $action->action }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-xs text-gray-500">
                            @if ($action->meta && isset($action->meta['reason']))
                                دلیل: {{ \Illuminate\Support\Str::limit($action->meta['reason'], 60) }} ·
                            @endif
                            {{ $action->subject_type ? class_basename($action->subject_type).'#'.\App\Support\PersianDate::digits($action->subject_id) : '—' }}
                        </td>
                        <td class="px-5 py-3 text-xs text-gray-400">{{ \App\Support\PersianDate::format($action->created_at, withTime: true) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center text-gray-400">رویدادی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $actions->links() }}
</div>
