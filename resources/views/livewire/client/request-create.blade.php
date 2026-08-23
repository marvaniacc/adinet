<div class="mx-auto max-w-3xl px-4 py-10">
    <a href="{{ route('lawyers.show', $profile->slug) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
        <span class="material-symbols-rounded text-base">arrow_forward</span>
        بازگشت به پروفایل وکیل
    </a>

    <h1 class="mt-4 text-2xl font-extrabold text-gray-900">درخواست مشاوره از {{ $profile->display_name }}</h1>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <form wire:submit="submit" class="mt-6 space-y-5 pb-16">
        {{-- Service --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-gray-900">۱. انتخاب خدمت *</h2>
            <div class="mt-4 space-y-3">
                @forelse ($services as $service)
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border p-4 text-sm transition has-checked:border-brand-500 has-checked:bg-brand-50/60"
                           wire:key="svc-{{ $service->id }}">
                        <span class="flex items-start gap-3">
                            <input type="radio" value="{{ $service->id }}" wire:model="service_id"
                                   class="mt-0.5 border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span>
                                <span class="block font-medium text-gray-900">{{ $service->title }}</span>
                                <span class="mt-0.5 block text-xs text-gray-500">
                                    {{ $service->consultation_type->label() }} · {{ \App\Support\PersianDate::digits($service->duration_minutes) }} دقیقه
                                    @if ($service->priceLabel()) · {{ $service->priceLabel() }} @endif
                                </span>
                            </span>
                        </span>
                    </label>
                @empty
                    <p class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-400">
                        این وکیل فعلاً خدمتی ثبت نکرده است.
                    </p>
                @endforelse
            </div>
            @error('service_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Issue details --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-gray-900">۲. شرح مسئله حقوقی</h2>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">موضوع *</label>
                    <input type="text" class="input" wire:model="subject" placeholder="مثال: اختلاف در قرارداد اجاره‌نامه" maxlength="150">
                    @error('subject') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">توضیحات کامل *</label>
                    <textarea rows="7" class="input" wire:model="description"
                              placeholder="ماجرا را از ابتدا شرح دهید: طرفین، تاریخ، اسناد موجود و خواسته شما..."></textarea>
                    @error('description') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-gray-400">اطلاعات هویتی و محرمانه را ارسال نکنید؛ جزئیات حساس را در جلسه مشاوره مطرح کنید.</p>
                </div>
            </div>
        </div>

        {{-- Preferred time --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-gray-900">۳. انتخاب زمان</h2>
            <p class="mt-1 text-xs text-gray-400">از زمان‌های خالی وکیل یکی را انتخاب کنید (اختیاری، ولی باعث تسریع تأیید می‌شود).</p>

            @if ($timeSlots->isNotEmpty())
                @php($byDate = $timeSlots->groupBy('date'))
                <div class="mt-4 space-y-4 max-h-72 overflow-y-auto pe-1">
                    @foreach ($byDate as $date => $daySlots)
                        <div wire:key="slot-date-{{ $date }}">
                            <p class="text-xs font-semibold text-gray-500">{{ \App\Support\PersianDate::format($daySlots[0]['datetime']) }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($daySlots as $slot)
                                    <button type="button"
                                            wire:click="$set('preferred_date', '{{ $slot['date'] }}'); $set('preferred_time', '{{ $slot['time'] }}')"
                                            wire:key="slot-{{ $slot['datetime']->timestamp }}"
                                            class="rounded-lg border px-3 py-1.5 text-xs transition {{ $preferred_date === $slot['date'] && $preferred_time === $slot['time'] ? 'border-brand-600 bg-brand-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-brand-300 hover:bg-brand-50' }}">
                                        {{ \App\Support\PersianDate::digits($slot['time']) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-3 rounded-xl border border-dashed border-gray-200 p-4 text-xs text-gray-400">
                    وکیل هنوز ساعات کاری تعریف نکرده است؛ می‌توانید تاریخ پیشنهادی خود را دستی وارد کنید.
                </p>
            @endif

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">تاریخ (شمسی، مثلاً ۱۴۰۵/۰۶/۲۰)</label>
                    <input type="text" dir="ltr" class="input text-right" wire:model="preferred_date" placeholder="1405/06/20">
                    @error('preferred_date') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">ساعت</label>
                    <input type="time" dir="ltr" class="input text-right" wire:model="preferred_time">
                    @error('preferred_time') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <button type="submit" class="btn-primary w-full py-3 text-base" wire:loading.attr="disabled" wire:target="submit">
            <span wire:loading.remove wire:target="submit">ثبت درخواست مشاوره</span>
            <span wire:loading wire:target="submit">در حال ارسال...</span>
        </button>
    </form>
</div>
