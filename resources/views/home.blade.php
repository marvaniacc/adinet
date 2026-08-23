<x-app-layout>
    <!-- Hero -->
    <section class="mx-auto max-w-6xl px-4 pb-16 pt-20 text-center sm:pt-28">
        <h1 class="mx-auto max-w-3xl text-4xl font-extrabold leading-tight text-gray-900 sm:text-5xl">
            مشاوره حقوقی،
            <span class="text-brand-600">با خیال راحت</span>
        </h1>
        <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-gray-500">
            آدینت شما را به وکلای تأییدشده متصل می‌کند. درخواست مشاوره ثبت کنید، با وکیل گفتگو کنید و نوبت خود را هماهنگ نمایید.
        </p>

        <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
            <a href="{{ route('lawyers.index') }}" class="btn-primary px-8 py-3 text-base">
                <span class="material-symbols-rounded">search</span>
                یافتن وکیل مناسب
            </a>
            <a href="{{ route('lawyer.register') }}" class="btn-secondary px-8 py-3 text-base">
                ثبت‌نام وکیل
            </a>
        </div>
    </section>

    <!-- How it works -->
    <section class="border-t border-gray-100 bg-gray-50 py-20">
        <div class="mx-auto max-w-6xl px-4">
            <h2 class="text-center text-2xl font-bold text-gray-900">چگونه کار می‌کند؟</h2>

            <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['person_search', '۱. یافتن وکیل', 'بر اساس تخصص و شهر، وکلای تأییدشده را جستجو کنید.'],
                    ['edit_document', '۲. ثبت درخواست', 'شرح مسئله حقوقی خود را برای وکیل ارسال کنید.'],
                    ['forum', '۳. گفتگو با وکیل', 'در چت امن آدینت با وکیل در ارتباط باشید.'],
                    ['event_available', '۴. هماهنگی نوبت', 'نوبت مشاوره را تعیین و جلسه را برگزار کنید.'],
                ] as [$icon, $title, $desc])
                    <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-sm">
                        <span class="material-symbols-rounded mx-auto block text-4xl text-brand-600">{{ $icon }}</span>
                        <h3 class="mt-4 font-bold text-gray-900">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-500">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-app-layout>
