<div>
    <h1 class="text-2xl font-bold text-gray-900">ساعات کاری</h1>
    <p class="mt-1 text-sm text-gray-500">بازه‌های تکرارشونده هفتگی خود را تعریف کنید تا موکلان بتوانند از میان زمان‌های خالی، نوبت رزرو کنند.</p>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    {{-- Add / edit form --}}
    <form wire:submit="save" class="mt-6 flex flex-wrap items-end gap-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">روز هفته {{ $editingId ? '(ویرایش)' : '' }}</label>
            <select class="input min-w-32" wire:model="weekday">
                @foreach ($weekdays as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('weekday') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">از ساعت</label>
            <input type="time" dir="ltr" class="input text-right" wire:model="start_time">
            @error('start_time') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">تا ساعت</label>
            <input type="time" dir="ltr" class="input text-right" wire:model="end_time">
            @error('end_time') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn-primary">{{ $editingId ? 'ذخیره' : 'افزودن بازه' }}</button>
        @if ($editingId)
            <button type="button" wire:click="$set('editingId', null)" class="btn-secondary">انصراف</button>
        @endif
    </form>

    {{-- Slots table --}}
    <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500">
                <tr>
                    <th class="px-5 py-3 text-right font-medium">روز</th>
                    <th class="px-5 py-3 text-right font-medium">بازه</th>
                    <th class="px-5 py-3 text-center font-medium">وضعیت</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($slots as $slot)
                    <tr wire:key="slot-{{ $slot->id }}" class="{{ ! $slot->is_active ? 'opacity-50' : '' }}">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $slot->weekdayLabel() }}</td>
                        <td dir="ltr" class="px-5 py-3 text-left text-gray-600">{{ $slot->start_time }} - {{ $slot->end_time }}</td>
                        <td class="px-5 py-3 text-center">
                            <button type="button" wire:click="toggle({{ $slot->id }})"
                                    class="rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 ring-inset {{ $slot->is_active ? 'bg-green-50 text-green-700 ring-green-200' : 'bg-gray-100 text-gray-500 ring-gray-200' }}">
                                {{ $slot->is_active ? 'فعال' : 'غیرفعال' }}
                            </button>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" wire:click="edit({{ $slot->id }})" class="rounded-lg p-2 text-gray-400 hover:bg-brand-50 hover:text-brand-600">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                                <button type="button" wire:click="delete({{ $slot->id }})" wire:confirm="حذف شود؟"
                                        class="rounded-lg p-2 text-gray-400 hover:bg-red-50 hover:text-red-600">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center text-gray-400">هنوز بازه‌ای تعریف نکرده‌اید.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Generated slot preview --}}
    @if ($previewCount > 0)
        <div class="mt-6 rounded-2xl border border-brand-200 bg-brand-50/40 p-5">
            <h2 class="text-sm font-semibold text-brand-800">پیش‌نمایش زمان‌های قابل رزرو ({{ \App\Support\PersianDate::digits($previewCount) }} اسلات در {{ \App\Support\PersianDate::digits(14) }} روز آینده)</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($preview as $slot)
                    <span class="rounded-lg bg-white px-2.5 py-1.5 text-xs text-gray-600 ring-1 ring-brand-200">
                        {{ \App\Support\PersianDate::format($slot['datetime']) }} - {{ \App\Support\PersianDate::digits($slot['time']) }}
                    </span>
                @endforeach
                @if ($previewCount > 12)
                    <span class="self-center text-xs text-gray-400">و {{ \App\Support\PersianDate::digits($previewCount - 12) }} مورد دیگر…</span>
                @endif
            </div>
        </div>
    @endif
</div>
