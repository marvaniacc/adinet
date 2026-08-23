<div>
    <h1 class="text-2xl font-bold text-gray-900">پنل مدیریت</h1>
    <p class="mt-1 text-sm text-gray-500">نمای کلی عملکرد آدینت.</p>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <a href="{{ route('admin.lawyers.verification') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="material-symbols-rounded text-2xl text-amber-500">hourglass_top</span>
                <span class="text-2xl font-extrabold text-gray-900">{{ \App\Support\PersianDate::digits($pendingCount) }}</span>
            </div>
            <p class="mt-3 text-sm font-medium text-gray-700">در انتظار تأیید</p>
            <p class="mt-1 text-xs text-gray-400">پروفایل‌های ارسال‌شده توسط وکلا</p>
        </a>

        <a href="{{ route('admin.lawyers.verification') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="material-symbols-rounded text-2xl text-green-600">verified</span>
                <span class="text-2xl font-extrabold text-gray-900">{{ \App\Support\PersianDate::digits($verifiedCount) }}</span>
            </div>
            <p class="mt-3 text-sm font-medium text-gray-700">وکلای تأییدشده</p>
            <p class="mt-1 text-xs text-gray-400">نمایش‌داده‌شده در بازارگاه عمومی</p>
        </a>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="material-symbols-rounded text-2xl text-brand-600">group</span>
                <span class="text-2xl font-extrabold text-gray-900">{{ \App\Support\PersianDate::digits($userCount) }}</span>
            </div>
            <p class="mt-3 text-sm font-medium text-gray-700">کاربران</p>
            <p class="mt-1 text-xs text-gray-400">موکلان، وکلا و مدیران</p>
        </div>
    </div>

    <div class="mt-6 grid gap-4 pb-8 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('admin.lawyers.verification') }}" class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3.5 text-sm font-medium text-gray-700 shadow-sm hover:border-brand-300">
            <span class="material-symbols-rounded text-xl text-brand-600">verified</span> تأیید وکلا
        </a>
        <a href="{{ route('admin.specialties') }}" class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3.5 text-sm font-medium text-gray-700 shadow-sm hover:border-brand-300">
            <span class="material-symbols-rounded text-xl text-brand-600">category</span> تخصص‌ها
        </a>
        <a href="{{ route('admin.cities') }}" class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3.5 text-sm font-medium text-gray-700 shadow-sm hover:border-brand-300">
            <span class="material-symbols-rounded text-xl text-brand-600">location_city</span> شهرها
        </a>
        <a href="{{ route('lawyers.index') }}" target="_blank" class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3.5 text-sm font-medium text-gray-700 shadow-sm hover:border-brand-300">
            <span class="material-symbols-rounded text-xl text-brand-600">public</span> بازارگاه ↗
        </a>
    </div>
</div>
