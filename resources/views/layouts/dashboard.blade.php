@php
    $user = auth()->user();

    $nav = match (true) {
        $user->isAdmin() => [
            ['label' => 'داشبورد', 'icon' => 'dashboard', 'href' => route('admin.dashboard'), 'active' => true],
            ['label' => 'تأیید وکلا', 'icon' => 'verified', 'href' => route('admin.lawyers.verification')],
            ['label' => 'وکلا', 'icon' => 'gavel', 'href' => route('admin.lawyers.index')],
            ['label' => 'موکلان', 'icon' => 'group', 'href' => route('admin.clients.index')],
            ['label' => 'درخواست‌ها', 'icon' => 'description', 'href' => route('admin.requests.index')],
            ['label' => 'نوبت‌ها', 'icon' => 'event', 'href' => route('admin.appointments.index')],
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
            ['label' => 'پروفایل', 'icon' => 'person', 'href' => route('dashboard.profile')],
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
    <button type="button" class="rounded-full p-2 text-gray-600 hover:bg-gray-100" x-on:click="drawerOpen = true" aria-label="منو">
        <span class="material-symbols-rounded">menu</span>
    </button>
    <a href="{{ route('home') }}" class="flex items-center gap-1.5 font-extrabold text-brand-700">
        <span class="material-symbols-rounded">balance</span>
        آدینت
    </a>
    <span class="w-9"></span>
</div>

<!-- Mobile drawer backdrop -->
<div class="fixed inset-0 z-40 bg-gray-900/50 md:hidden" x-show="drawerOpen" x-on:click="drawerOpen = false" x-transition.opacity x-cloak></div>

{{-- Desktop: icon rail that expands on hover (overlays content).
    Mobile: off-canvas drawer at full label width. --}}
<aside
    class="group/sidebar fixed inset-y-0 right-0 z-50 overflow-y-auto overflow-x-hidden border-l border-gray-200 bg-white transition-all duration-200
           w-64 translate-x-full md:translate-x-0 md:w-[76px] md:hover:w-64 md:hover:shadow-2xl"
    :class="drawerOpen && '!translate-x-0'"
    x-cloak
>
    {{-- Brand --}}
    <div class="flex h-16 items-center gap-2.5 overflow-hidden whitespace-nowrap border-b border-gray-100 px-5">
        <a href="{{ route('home') }}" class="flex flex-none items-center gap-2 font-extrabold text-brand-700">
            <span class="material-symbols-rounded text-2xl">balance</span>
            <span class="md:opacity-0 md:transition-opacity md:duration-200 md:group-hover/sidebar:opacity-100">آدینت</span>
        </a>
        <button type="button" class="flex-none rounded-full p-1.5 text-gray-500 hover:bg-gray-100 md:hidden" x-on:click="drawerOpen = false" aria-label="بستن">
            <span class="material-symbols-rounded">close</span>
        </button>
    </div>

    {{-- User --}}
    <div class="overflow-hidden whitespace-nowrap border-b border-gray-100 px-5 py-4">
        <div class="flex items-center gap-3">
            <span class="material-symbols-rounded flex-none text-2xl text-brand-600">account_circle</span>
            <div class="md:opacity-0 md:transition-opacity md:duration-200 md:group-hover/sidebar:opacity-100">
                <p class="text-sm font-semibold text-gray-900">{{ $user->fullName() }}</p>
                <p dir="ltr" class="text-right text-xs text-gray-400">{{ $user->mobile }}</p>
                <span class="badge mt-1 bg-brand-50 text-brand-700 ring-brand-200">
                    {{ match ($user->role) {
                        App\Models\User::ROLE_ADMIN => 'مدیر',
                        App\Models\User::ROLE_LAWYER => 'وکیل',
                        default => 'موکل',
                    } }}
                </span>
            </div>
        </div>
    </div>

    {{-- Nav --}}
    <nav class="space-y-1 p-3">
        @foreach ($nav as $item)
            <a href="{{ $item['href'] }}"
               wire:key="nav-{{ $loop->index }}"
               class="flex items-center gap-3 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-medium transition
                      {{ ($item['active'] ?? false) ? 'bg-brand-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <span class="material-symbols-rounded flex-none text-xl">{{ $item['icon'] }}</span>
                <span class="md:opacity-0 md:transition-opacity md:duration-200 md:group-hover/sidebar:opacity-100">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Logout --}}
    <div class="border-t border-gray-100 p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-red-50 hover:text-red-600">
                <span class="material-symbols-rounded flex-none text-xl">logout</span>
                <span class="md:opacity-0 md:transition-opacity md:duration-200 md:group-hover/sidebar:opacity-100">خروج از حساب</span>
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
