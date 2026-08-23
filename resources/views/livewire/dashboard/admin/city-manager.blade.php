<div>
    <h1 class="text-2xl font-bold text-gray-900">شهرها</h1>
    <p class="mt-1 text-sm text-gray-500">مدیریت شهرهای قابل انتخاب برای وکلا.</p>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <form wire:submit="save" class="mt-6 flex flex-wrap items-end gap-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="min-w-48 grow">
            <label class="mb-1.5 block text-sm font-medium text-gray-700">نام شهر {{ $editingId ? '(ویرایش)' : '' }}</label>
            <input type="text" class="input" wire:model="name" placeholder="مثال: تهران">
            @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn-primary">{{ $editingId ? 'ذخیره' : 'افزودن' }}</button>
        @if ($editingId)
            <button type="button" wire:click="$set('editingId', null); \$set('name', '')" class="btn-secondary">انصراف</button>
        @endif
    </form>

    <div class="mt-6 grid grid-cols-2 gap-3 pb-8 sm:grid-cols-3 lg:grid-cols-4">
        @forelse ($cities as $city)
            <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm {{ ! $city->is_active ? 'opacity-50' : '' }}" wire:key="city-{{ $city->id }}">
                <span class="font-medium text-gray-900">{{ $city->name }}</span>
                <div class="flex items-center gap-0.5">
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $city->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $city->is_active ? 'فعال' : 'غیرفعال' }}
                    </span>
                    <button type="button" wire:click="toggle({{ $city->id }})" class="rounded-lg p-1.5 text-gray-400 hover:bg-brand-50 hover:text-brand-600" title="{{ $city->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}">
                        <span class="material-symbols-outlined text-base">{{ $city->is_active ? 'visibility' : 'visibility_off' }}</span>
                    </button>
                    <button type="button" wire:click="edit({{ $city->id }})" class="rounded-lg p-1.5 text-gray-400 hover:bg-brand-50 hover:text-brand-600" title="ویرایش">
                        <span class="material-symbols-outlined text-base">edit</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center text-sm text-gray-400">شهری ثبت نشده است.</div>
        @endforelse
    </div>
</div>
