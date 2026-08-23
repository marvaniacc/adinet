@php
    $user = auth()->user();

    /*
     * Active state derives from the current route name via routeIs()
     * patterns - nothing is hardcoded per position.
     */
    $nav = match (true) {
        $user->isAdmin() => [
            ['label' => 'داشبورد', 'icon' => 'dashboard', 'svg' => 'dashboard', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'تأیید وکلا', 'icon' => 'verified', 'svg' => 'verified', 'href' => route('admin.lawyers.verification'), 'active' => request()->routeIs('admin.lawyers.verification')],
            ['label' => 'وکلا', 'icon' => 'gavel', 'svg' => 'lawyer', 'href' => route('admin.lawyers.index'), 'active' => request()->routeIs('admin.lawyers.index')],
            ['label' => 'موکلان', 'icon' => 'group', 'svg' => 'clients', 'href' => route('admin.clients.index'), 'active' => request()->routeIs('admin.clients.index')],
            ['label' => 'درخواست‌ها', 'icon' => 'description', 'svg' => 'request', 'href' => route('admin.requests.index'), 'active' => request()->routeIs('admin.requests.index')],
            ['label' => 'نوبت‌ها', 'icon' => 'event', 'svg' => 'appointment', 'href' => route('admin.appointments.index'), 'active' => request()->routeIs('admin.appointments.index')],
            ['label' => 'نظرات', 'icon' => 'reviews', 'svg' => 'review', 'href' => route('admin.reviews'), 'active' => request()->routeIs('admin.reviews')],
            ['label' => 'پرداخت‌ها', 'icon' => 'payments', 'svg' => 'payment', 'href' => route('admin.payments'), 'active' => request()->routeIs('admin.payments')],
            ['label' => 'گزارشات', 'icon' => 'summarize', 'svg' => 'report', 'href' => route('admin.reports.index'), 'active' => request()->routeIs('admin.reports.*')],
            ['label' => 'تخصص‌ها', 'icon' => 'category', 'svg' => 'specialty', 'href' => route('admin.specialties'), 'active' => request()->routeIs('admin.specialties')],
            ['label' => 'شهرها', 'icon' => 'location_city', 'svg' => 'city', 'href' => route('admin.cities'), 'active' => request()->routeIs('admin.cities')],
        ],
        $user->isLawyer() => [
            ['label' => 'پنل وکیل', 'icon' => 'dashboard', 'svg' => 'dashboard', 'href' => route('dashboard.lawyer.index'), 'active' => request()->routeIs('dashboard.lawyer.index')],
            ['label' => 'درخواست‌های مشاوره', 'icon' => 'description', 'svg' => 'request', 'href' => route('dashboard.lawyer.requests'), 'active' => request()->routeIs('dashboard.lawyer.requests')],
            ['label' => 'نوبت‌ها', 'icon' => 'event', 'svg' => 'appointment', 'href' => route('dashboard.lawyer.appointments'), 'active' => request()->routeIs('dashboard.lawyer.appointments')],
            ['label' => 'ساعات کاری', 'icon' => 'schedule', 'svg' => 'availability', 'href' => route('dashboard.lawyer.availability'), 'active' => request()->routeIs('dashboard.lawyer.availability')],
            ['label' => 'پیام‌ها', 'icon' => 'forum', 'svg' => 'message', 'href' => route('dashboard.lawyer.messages.index'), 'active' => request()->routeIs('dashboard.lawyer.messages.*')],
            ['label' => 'خدمات مشاوره', 'icon' => 'miscellaneous_services', 'svg' => 'service', 'href' => route('dashboard.lawyer.services'), 'active' => request()->routeIs('dashboard.lawyer.services')],
            ['label' => 'پروفایل حرفه‌ای', 'icon' => 'badge', 'svg' => 'profile', 'href' => route('dashboard.lawyer.profile'), 'active' => request()->routeIs('dashboard.lawyer.profile')],
            ['label' => 'نظرات موکلان', 'icon' => 'reviews', 'svg' => 'review', 'href' => route('dashboard.lawyer.reviews'), 'active' => request()->routeIs('dashboard.lawyer.reviews')],
        ],
        default => [
            ['label' => 'داشبورد', 'icon' => 'dashboard', 'svg' => 'dashboard', 'href' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
            ['label' => 'درخواست‌های من', 'icon' => 'description', 'svg' => 'request', 'href' => route('dashboard.requests'), 'active' => request()->routeIs('dashboard.requests')],
            ['label' => 'نوبت‌های من', 'icon' => 'event', 'svg' => 'appointment', 'href' => route('dashboard.appointments'), 'active' => request()->routeIs('dashboard.appointments')],
            ['label' => 'پیام‌ها', 'icon' => 'forum', 'svg' => 'message', 'href' => route('messages.index'), 'active' => request()->routeIs('messages.*')],
            ['label' => 'پروفایل', 'icon' => 'person', 'svg' => 'profile', 'href' => route('dashboard.profile'), 'active' => request()->routeIs('dashboard.profile')],
            ['label' => 'نظرات من', 'icon' => 'reviews', 'svg' => 'review', 'href' => route('reviews.index'), 'active' => request()->routeIs('reviews.index')],
        ],
    };

    $roleLabel = match ($user->role) {
        App\Models\User::ROLE_ADMIN => 'مدیر',
        App\Models\User::ROLE_LAWYER => 'وکیل',
        default => 'موکل',
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

{{-- ===== Desktop top header (persistent on every dashboard page) ===== --}}
<header class="sticky top-0 z-30 hidden h-16 items-center justify-between gap-4 border-b border-gray-200 bg-white/95 px-6 backdrop-blur md:flex md:mr-[76px]">
    <div class="flex items-center gap-2 text-sm text-gray-400">
        <span class="material-symbols-rounded text-xl text-brand-600">balance</span>
        <span class="font-semibold text-gray-700">آدینت</span>
        <span class="text-gray-300">/</span>
        <span>{{ $roleLabel }}</span>
    </div>

    {{-- User info block (moved from sidebar) --}}
    <div class="flex items-center gap-3">
        <div class="text-end leading-tight">
            <p class="text-sm font-semibold text-gray-900">{{ $user->fullName() }}</p>
            <p dir="ltr" class="text-[11px] text-gray-400">{{ $user->mobile }}</p>
        </div>
        <span class="badge bg-brand-50 text-brand-700 ring-brand-200">{{ $roleLabel }}</span>
        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 ring-1 ring-brand-100">
            <span class="material-symbols-rounded text-xl text-brand-600">person</span>
        </span>
    </div>
</header>

{{-- ===== Mobile top bar ===== --}}
<div class="sticky top-0 z-40 flex h-14 items-center justify-between border-b border-gray-200 bg-white px-4 md:hidden">
    <button type="button" class="rounded-full p-2 text-gray-600 hover:bg-gray-100" x-on:click="drawerOpen = true" aria-label="منو">
        <span class="material-symbols-rounded">menu</span>
    </button>
    <a href="{{ route('home') }}" class="flex items-center gap-1.5 font-extrabold text-brand-700">
        <span class="material-symbols-rounded">balance</span>
        آدینت
    </a>
    <a href="{{ route($user->isLawyer() ? 'dashboard.lawyer.profile' : 'dashboard.profile') }}" class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 ring-1 ring-brand-100" aria-label="پروفایل">
        <span class="material-symbols-rounded text-lg text-brand-600">person</span>
    </a>
</div>

<!-- Mobile drawer backdrop -->
<div class="fixed inset-0 z-40 bg-gray-900/50 md:hidden" x-show="drawerOpen" x-on:click="drawerOpen = false" x-transition.opacity x-cloak></div>

{{-- ===== Sidebar =====
     Desktop: icon rail that expands when the pointer enters the WHOLE
     container (Alpine mouseenter/mouseleave - no CSS :hover width dance,
     so moving toward labels/scrollbar cannot collapse it).
     Mobile: off-canvas drawer at full label width. --}}
<aside
    x-data="{ railOpen: false }"
    x-on:mouseenter="if (window.innerWidth >= 768) railOpen = true"
    x-on:mouseleave="railOpen = false"
    x-bind:class="{
        'translate-x-0 shadow-2xl': drawerOpen || railOpen,
        'md:w-64': railOpen || drawerOpen,
        'md:w-[76px]': ! (railOpen || drawerOpen),
    }"
    class="fixed inset-y-0 right-0 z-50 w-64 overflow-y-auto overflow-x-hidden border-l border-gray-200 bg-white transition-all duration-200 translate-x-full md:translate-x-0"
    x-cloak
>
    {{-- Brand --}}
    <div class="flex h-16 flex-none items-center gap-2.5 whitespace-nowrap border-b border-gray-100 px-5">
        <a href="{{ route('home') }}" class="flex flex-none items-center gap-2 font-extrabold text-brand-700">
            <span class="material-symbols-rounded flex-none text-2xl">balance</span>
            <span :class="railOpen ? '' : 'md:hidden'">آدینت</span>
        </a>
        <button type="button" class="flex-none rounded-full p-1.5 text-gray-500 hover:bg-gray-100 md:hidden" x-on:click="drawerOpen = false" aria-label="بستن">
            <span class="material-symbols-rounded">close</span>
        </button>
    </div>

    {{-- Nav --}}
    <nav class="space-y-1 p-3">
        @foreach ($nav as $item)
            <a href="{{ $item['href'] }}"
               wire:key="nav-{{ $loop->index }}"
               @click="drawerOpen = false"
               :class="railOpen ? 'px-3' : 'md:justify-center md:px-0'"
               class="flex items-center gap-3 whitespace-nowrap rounded-xl py-2.5 text-sm font-medium transition-colors
                      {{ $item['active']
                          ? 'bg-brand-50 font-semibold text-brand-700'
                          : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
               {!! $item['active'] ? 'aria-current="page"' : '' !!}>
                <x-svg-icon name="{{ $item['svg'] }}" fallback="{{ $item['icon'] }}" class="h-5 w-5 flex-none"/>
                <span :class="railOpen ? '' : 'md:hidden'">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Logout pinned at bottom --}}
    <div class="border-t border-gray-100 p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" :class="railOpen ? 'w-full px-3' : 'md:w-full md:justify-center md:px-0'"
                    class="flex items-center gap-3 whitespace-nowrap rounded-xl py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-red-50 hover:text-red-600">
                <span class="material-symbols-rounded flex-none text-xl">logout</span>
                <span :class="railOpen ? '' : 'md:hidden'">خروج از حساب</span>
            </button>
        </form>
    </div>
</aside>

<!-- Main content -->
<main class="min-h-screen pt-14 md:mr-[76px] md:pt-0">
    <div class="mx-auto max-w-5xl p-4 sm:p-6 lg:p-8">
        {{ $slot }}
    </div>
</main>

@livewireScripts
</body>
</html>
