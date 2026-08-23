<div>
    <h1 class="text-2xl font-bold text-gray-900">سلام، {{ $user->fullName() }} 👋</h1>

    <p class="mt-2 text-sm text-gray-500">
        از این بخش درخواست‌های مشاوره و نوبت‌های خود را مدیریت کنید.
    </p>

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <span class="material-symbols-rounded text-brand-600">person_search</span>
            <p class="mt-3 text-sm font-medium text-gray-900">یافتن وکیل</p>
            <p class="mt-1 text-xs leading-relaxed text-gray-400">از میان وکلای تأییدشده آدینت، وکیل مناسب خود را انتخاب کنید.</p>
            <a href="{{ route('lawyers.index') }}" class="btn-secondary mt-4 !py-2 !text-xs">مشاهده وکلا</a>
        </div>
        <a href="{{ route('dashboard.requests') }}" class="rounded-2xl border border-dashed border-gray-300 bg-white p-5 hover:border-brand-300">
            <span class="material-symbols-rounded text-2xl text-brand-600">description</span>
            <p class="mt-2 font-medium text-gray-900">درخواست‌های من</p>
            <p class="mt-1 text-xs leading-relaxed text-gray-400">پیگیری وضعیت درخواست‌های ثبت‌شده شما.</p>
        </a>
        <a href="{{ route('dashboard.appointments') }}" class="rounded-2xl border border-dashed border-gray-300 bg-white p-5 hover:border-brand-300">
            <span class="material-symbols-rounded text-2xl text-brand-600">event</span>
            <p class="mt-2 font-medium text-gray-900">نوبت‌های من</p>
            <p class="mt-1 text-xs leading-relaxed text-gray-400">نوبت‌های هماهنگ‌شده با وکلا.</p>
        </a>
    </div>
</div>
