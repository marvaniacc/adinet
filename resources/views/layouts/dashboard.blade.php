@php
    $user = auth()->user();

    $nav = match (true) {
        $user->isAdmin() => [
            ['label' => 'داشبورد', 'icon' => 'dashboard', 'href' => route('admin.dashboard'), 'active' => true],
            ['label' => 'تأیید وکلا', 'icon' => 'verified', 'href' => route('admin.lawyers.verification')],
            ['label' => 'وکلا', 'icon' => 'gavel', 'href' => '#'],
            ['label' => 'موکلان', 'icon' => 'group', 'href' => '#'],
            ['label' => 'درخواست‌ها', 'icon' => 'description', 'href' => '#'],
            ['label' => 'نوبت‌ها', 'icon' => 'event', 'href' => '#'],
            ['label' => 'نظرات', 'icon' => 'reviews', 'href' => route('admin.reviews')],
            ['label' => 'پرداخت‌ها', 'icon' => 'payments', 'href' => route('admin.payments')],
            ['label' => 'گزارشات', 'icon' => 'summarize', 'href' => route('admin.reports.index')],
            ['label' => 'تخصص‌ها', 'icon' => 'category', 'href' => route('admin.specialties')],
            ['label' => 'شهرها', 'icon' => 'location_city', 'href' => route('admin.cities')],
        ],
        $user->isLawyer() => [
            ['label' => 'پنل وکیل', 'icon' => 'dashboard', 'href' => route('dashboard.lawyer.index'), 'active' => true],
            ['label' => 'درخواست‌های مشاوره', 'icon' => 'description', 'href' => route('dashboard.lawyer.requests')],
            ['label' => 'نوبت‌ها', 'icon' => 'event', 'href' => route('dashboard.lawyer.appointments')],
            ['label' => 'ساعات کاری', 'icon' => 'schedule', 'href' => route('dashboard.lawyer.availability')],
            ['label' => 'پیام‌ها', 'icon' => 'forum', 'href' => route('dashboard.lawyer.messages.index')],
            ['label' => 'خدمات مشاوره', 'icon' => 'miscellaneous_services', 'href' => route('dashboard.lawyer.services')],
            ['label' => 'پروفایل حرفه‌ای', 'icon' => 'badge', 'href' => route('dashboard.lawyer.profile')],
            ['label' => 'نظرات موکلان', 'icon' => 'reviews', 'href' => route('dashboard.lawyer.reviews')],
        ],
        default => [
            ['label' => 'داشبورد', 'icon' => 'dashboard', 'href' => route('dashboard'), 'active' => true],
            ['label' => 'درخواست‌های من', 'icon' => 'description', 'href' => route('dashboard.requests')],
            ['label' => 'نوبت‌های من', 'icon' => 'event', 'href' => route('dashboard.appointments')],
            ['label' => 'پیام‌ها', 'icon' => 'forum', 'href' => route('messages.index')],
            ['label' => 'پروفایل', 'icon' => 'person', 'href' => '#'],
            ['label' => 'نظرات من', 'icon' => 'reviews', 'href' => route('reviews.index')],
        ],
    };
@endphp

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'داشبورد | آدینت' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-screen bg-gray-50 antialiased" x-data="{ drawerOpen: false }">

<!-- Mobile top bar -->
<div class="sticky top-0 z-40 flex h-14 items-center justify-between border-b border-gray-200 bg-white px-4 md:hidden">
    <button type="button" class="rounded-lg p-2 text-gray-600 hover:bg-gray-100" x-on:click="drawerOpen = true" aria-label="منو">
        <span class="material-symbols-outlined">menu</span>
    </button>
    <a href="{{ route('home') }}" class="flex items-center gap-1.5 font-extrabold text-brand-700">
        <span class="material-symbols-outlined">balance</span>
        آدینت
    </a>
    <span class="w-9"></span>
</div>

<!-- Mobile drawer backdrop -->
<div class="fixed inset-0 z-40 bg-gray-900/50 md:hidden" x-show="drawerOpen" x-on:click="drawerOpen = false" x-transition.opacity x-cloak></div>

<!-- Sidebar -->
<aside
    class="fixed inset-y-0 right-0 z-50 w-72 translate-x-full overflow-y-auto border-l border-gray-200 bg-white transition-transform duration-200 md:translate-x-0"
    :class="drawerOpen && '!translate-x-0'"
    x-cloak
>
    <div class="flex h-16 items-center justify-between border-b border-gray-100 px-5">
        <a href="{{ route('home') }}" class="flex items-center gap-2 font-extrabold text-brand-700">
            <span class="material-symbols-outlined">balance</span>
            آدینت
        </a>
        <button type="button" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 md:hidden" x-on:click="drawerOpen = false" aria-label="بستن">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <div class="border-b border-gray-100 px-5 py-4">
        <p class="text-sm font-semibold text-gray-900">{{ $user->fullName() }}</p>
        <p dir="ltr" class="mt-0.5 text-right text-xs text-gray-400">{{ $user->mobile }}</p>
        <span class="mt-2 inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-200">
            {{ match ($user->role) {
                App\Models\User::ROLE_ADMIN => 'مدیر',
                App\Models\User::ROLE_LAWYER => 'وکیل',
                default => 'موکل',
            } }}
        </span>
    </div>

    <nav class="space-y-1 p-3">
        @foreach ($nav as $item)
            <a href="{{ $item['href'] }}"
               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                      {{ ($item['active'] ?? false) ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <span class="material-symbols-outlined text-xl">{{ $item['icon'] }}</span>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="border-t border-gray-100 p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-red-50 hover:text-red-600">
                <span class="material-symbols-outlined text-xl">logout</span>
                خروج از حساب
            </button>
        </form>
    </div>
</aside>

<!-- Main content -->
<main class="min-h-screen pt-14 md:mr-72 md:pt-0">
    <div class="mx-auto max-w-5xl p-4 sm:p-6 lg:p-8">
        {{ $slot }}
    </div>
</main>

@livewireScripts
</body>
</html>
