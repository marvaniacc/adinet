<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">پروفایل حرفه‌ای</h1>
            <p class="mt-1 text-sm text-gray-500">اطلاعات حرفه‌ای خود را تکمیل کنید تا پس از تأیید، در آدینت منتشر شود.</p>
        </div>

        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset {{ $profile->status->color() }}">
            {{ $profile->status->label() }}
        </span>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    @if ($profile->status === \App\Enums\LawyerStatus::Rejected && $profile->rejection_reason)
        <div class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm leading-relaxed text-red-700">
            <strong>دلیل رد پروفایل:</strong> {{ $profile->rejection_reason }}
        </div>
    @endif

    @if ($this->profileCreated)
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">تغییرات ذخیره شد.</div>
    @endif

    <form wire:submit="save" class="mt-6 space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-gray-900">اطلاعات پایه</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">نام نمایشی *</label>
                    <input type="text" class="input" wire:model="display_name" placeholder="مثال: دکتر علی رضایی">
                    @error('display_name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">شهر</label>
                    <select class="input" wire:model="city_id">
                        <option value="">— انتخاب شهر —</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </select>
                    @error('city_id') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">تلفن دفتر *</label>
                    <input type="text" dir="ltr" class="input text-right" wire:model="phone" placeholder="021-xxxxxxxx">
                    @error('phone') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">سابقه کار (سال) *</label>
                    <input type="number" min="0" max="70" class="input" wire:model="years_of_experience">
                    @error('years_of_experience') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-gray-900">مجوز حرفه‌ای</h2>
            <p class="mt-1 text-xs text-gray-400">این اطلاعات برای احراز صلاحیت شما بررسی می‌شود و به‌صورت عمومی نمایش داده خواهد شد.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">مرجع صدور پروانه *</label>
                    <select class="input" wire:model="bar_association">
                        <option value="">— انتخاب کنید —</option>
                        <option value="کانون وکلای دادگستری مرکز">کانون وکلای دادگستری مرکز</option>
                        <option value="مرکز وکلای قوه قضائیه">مرکز وکلای قوه قضائیه</option>
                        <option value="کانون وکلای دادگستری استان">کانون وکلای دادگستری استان</option>
                        <option value="سایر">سایر</option>
                    </select>
                    @error('bar_association') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">شماره پروانه *</label>
                    <input type="text" dir="ltr" class="input text-right" wire:model="license_number">
                    @error('license_number') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-gray-900">تخصص‌ها *</h2>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($specialties as $specialty)
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2.5 text-sm transition has-checked:border-brand-500 has-checked:bg-brand-50 has-checked:text-brand-700" wire:key="spec-{{ $specialty->id }}">
                        <input type="checkbox" value="{{ $specialty->id }}" wire:model="specialty_ids" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        {{ $specialty->name }}
                    </label>
                @endforeach
            </div>
            @error('specialty_ids') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-gray-900">معرفی حرفه‌ای *</h2>
            <textarea rows="6" class="input mt-4" wire:model="bio" placeholder="تحصیلات، سابقه و حوزه‌های تخصصی خود را معرفی کنید..."></textarea>
            @error('bio') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-gray-900">تصویر پروفایل</h2>
            <div class="mt-4 flex items-center gap-4">
                @if ($this->photo)
                    <img src="{{ $this->photo->temporaryUrl() }}" class="h-16 w-16 rounded-full object-cover ring-2 ring-brand-100" alt="">
                @elseif ($profile->profile_photo)
                    <img src="{{ Storage::url($profile->profile_photo) }}" class="h-16 w-16 rounded-full object-cover ring-2 ring-brand-100" alt="">
                @else
                    <span class="material-symbols-rounded rounded-full bg-gray-100 p-4 text-3xl text-gray-400">person</span>
                @endif
                <div>
                    <input type="file" wire:model="photo" accept=".jpg,.jpeg,.png,.webp"
                           class="block w-full max-w-xs text-sm text-gray-500 file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                    <p class="mt-1 text-xs text-gray-400">JPG، PNG یا WebP - حداکثر ۲ مگابایت</p>
                </div>
            </div>
            @error('photo') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            <div wire:loading wire:target="photo" class="mt-2 text-sm text-gray-500">در حال بارگذاری تصویر...</div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pb-8">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                ذخیره تغییرات
            </button>

            @if ($profile->canSubmitForReview())
                <button type="button" wire:click="submitForReview" wire:loading.attr="disabled" wire:target="submitForReview"
                        class="btn-secondary !border-green-300 !text-green-700 hover:!bg-green-50">
                    ارسال برای تأیید
                </button>
            @elseif ($profile->status === \App\Enums\LawyerStatus::PendingReview)
                <span class="flex items-center gap-2 text-sm text-amber-600">
                    <span class="material-symbols-rounded text-base">hourglass_top</span>
                    در انتظار بررسی توسط پشتیبانی آدینت
                </span>
            @endif
        </div>
    </form>
</div>
