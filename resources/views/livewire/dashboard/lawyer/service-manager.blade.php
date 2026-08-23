<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">خدمات مشاوره</h1>
            <p class="mt-1 text-sm text-gray-500">خدمات مشاوره خود را تعریف کنید تا موکلان بتوانند درخواست دهند.</p>
        </div>
        <button type="button" wire:click="openCreateForm" class="btn-primary">
            <span class="material-symbols-outlined text-base">add</span>
            خدمت جدید
        </button>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    {{-- Form (create/edit) --}}
    @if ($showForm)
        <form wire:submit="{{ $editingId ? 'update' : 'create' }}" class="mt-6 rounded-2xl border border-brand-200 bg-brand-50/40 p-6 shadow-sm">
            <h2 class="font-semibold text-gray-900">{{ $editingId ? 'ویرایش خدمت' : 'خدمت جدید' }}</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">عنوان *</label>
                    <input type="text" class="input" wire:model="title" placeholder="مثال: مشاوره تلفنی ۳۰ دقیقه‌ای">
                    @error('title') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">نوع مشاوره *</label>
                    <select class="input" wire:model="consultation_type">
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    @error('consultation_type') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">مدت (دقیقه) *</label>
                    <input type="number" min="10" max="480" step="5" class="input" wire:model="duration_minutes">
                    @error('duration_minutes') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">قیمت (تومان)</label>
                    <input type="number" min="0" class="input" wire:model="price_toman" placeholder="اختیاری - برای نمایش اطلاعاتی">
                    @error('price_toman') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        فعال و قابل نمایش
                    </label>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">توضیحات</label>
                    <textarea rows="3" class="input" wire:model="description" placeholder="شرح کوتاه خدمت..."></textarea>
                    @error('description') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-5 flex gap-3">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">{{ $editingId ? 'ذخیره' : 'افزودن' }}</button>
                <button type="button" wire:click="closeForm" class="btn-secondary">انصراف</button>
            </div>
        </form>
    @endif

    {{-- List --}}
    <div class="mt-6 space-y-3 pb-8">
        @forelse ($services as $service)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" wire:key="svc-{{ $service->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-gray-900">{{ $service->title }}</h3>
                            @if (! $service->is_active)
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">غیرفعال</span>
                            @endif
                        </div>
                        <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
                            <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-base text-brand-600">{{ match($service->consultation_type) { \App\Enums\ConsultationType::Phone => 'call', \App\Enums\ConsultationType::Online => 'chat', default => 'groups' } }}</span>{{ $service->consultation_type->label() }}</span>
                            <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-base text-gray-400">schedule</span>{{ $service->duration_minutes }} دقیقه</span>
                            @if ($service->priceLabel())
                                <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-base text-gray-400">payments</span>{{ $service->priceLabel() }}</span>
                            @endif
                        </p>
                        @if ($service->description)
                            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gray-500">{{ $service->description }}</p>
                        @endif
                    </div>

                    <div class="flex shrink-0 items-center gap-1.5">
                        <button type="button" wire:click="toggle({{ $service->id }})" title="{{ $service->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}"
                                class="rounded-lg p-2 {{ $service->is_active ? 'text-green-600 hover:bg-green-50' : 'text-gray-400 hover:bg-gray-100' }}">
                            <span class="material-symbols-outlined text-xl">{{ $service->is_active ? 'toggle_on' : 'toggle_off' }}</span>
                        </button>
                        <button type="button" wire:click="edit({{ $service->id }})" title="ویرایش" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-brand-600">
                            <span class="material-symbols-outlined text-xl">edit</span>
                        </button>
                        <button type="button" wire:click="delete({{ $service->id }})" title="حذف"
                                wire:confirm="این خدمت حذف شود؟"
                                class="rounded-lg p-2 text-gray-500 hover:bg-red-50 hover:text-red-600">
                            <span class="material-symbols-outlined text-xl">delete</span>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center">
                <span class="material-symbols-outlined mx-auto block text-4xl text-gray-300">miscellaneous_services</span>
                <p class="mt-3 font-medium text-gray-900">هنوز خدمتی ثبت نکرده‌اید</p>
                <p class="mt-1 text-sm text-gray-500">برای دریافت درخواست مشاوره، ابتدا خدمات خود را تعریف کنید.</p>
            </div>
        @endforelse
    </div>
</div>
