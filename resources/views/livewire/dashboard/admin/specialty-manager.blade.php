<div>
    <h1 class="text-2xl font-bold text-gray-900">تخصص‌ها</h1>
    <p class="mt-1 text-sm text-gray-500">مدیریت تخصص‌های حقوقی قابل انتخاب برای وکلا.</p>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <form wire:submit="save" class="mt-6 flex flex-wrap items-end gap-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="min-w-48 grow">
            <label class="mb-1.5 block text-sm font-medium text-gray-700">نام تخصص {{ $editingId ? '(ویرایش)' : '' }}</label>
            <input type="text" class="input" wire:model="name" placeholder="مثال: ملکی">
            @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="w-44">
            <label class="mb-1.5 block text-sm font-medium text-gray-700">اسلاگ (اختیاری)</label>
            <input type="text" dir="ltr" class="input text-right" wire:model="slug" placeholder="property">
            @error('slug') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn-primary">{{ $editingId ? 'ذخیره' : 'افزودن' }}</button>
        @if ($editingId)
            <button type="button" wire:click="$set('editingId', null); \$set('name', ''); \$set('slug', '')" class="btn-secondary">انصراف</button>
        @endif
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500">
                <tr>
                    <th class="px-5 py-3 text-right font-medium">نام</th>
                    <th class="px-5 py-3 text-right font-medium">اسلاگ</th>
                    <th class="px-5 py-3 text-right font-medium">وکلا</th>
                    <th class="px-5 py-3 text-center font-medium">وضعیت</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($specialties as $specialty)
                    <tr wire:key="spc-{{ $specialty->id }}" class="{{ ! $specialty->is_active ? 'opacity-50' : '' }}">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $specialty->name }}</td>
                        <td dir="ltr" class="px-5 py-3 text-left text-xs text-gray-400">{{ $specialty->slug }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ \App\Support\PersianDate::digits($specialty->lawyer_profiles_count) }}</td>
                        <td class="px-5 py-3 text-center">
                            <button type="button" wire:click="toggle({{ $specialty->id }})"
                                    class="rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 ring-inset {{ $specialty->is_active ? 'bg-green-50 text-green-700 ring-green-200' : 'bg-gray-100 text-gray-500 ring-gray-200' }}">
                                {{ $specialty->is_active ? 'فعال' : 'غیرفعال' }}
                            </button>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" wire:click="edit({{ $specialty->id }})" class="rounded-lg p-2 text-gray-400 hover:bg-brand-50 hover:text-brand-600" title="ویرایش">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                                <button type="button" wire:click="delete({{ $specialty->id }})" wire:confirm="حذف شود؟ ارتباط وکلا با این تخصص نیز حذف خواهد شد."
                                        class="rounded-lg p-2 text-gray-400 hover:bg-red-50 hover:text-red-600" title="حذف">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-gray-400">تخصصی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
