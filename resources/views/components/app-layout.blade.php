<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'آدینت | پلتفرم مشاوره حقوقی' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white antialiased">

<header class="sticky top-0 z-40 border-b border-gray-100 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-extrabold text-brand-700">
            <span class="material-symbols-outlined">balance</span>
            آدینت
        </a>

        <nav class="hidden items-center gap-6 text-sm font-medium text-gray-600 md:flex">
            <a href="{{ route('lawyers.index') }}" class="hover:text-gray-900">وکلا</a>
            <a href="{{ route('lawyers.index') }}" class="hover:text-gray-900">تخصص‌ها</a>
        </nav>

        <div class="flex items-center gap-3">
            @auth
                <a href="{{ auth()->user()->dashboardUrl() }}" class="btn-primary !py-2">
                    داشبورد
                </a>
            @else
                <a href="{{ route('lawyer.register') }}" class="hidden text-sm font-medium text-gray-600 hover:text-gray-900 sm:block">
                    ثبت‌نام وکیل
                </a>
                <a href="{{ route('login') }}" class="btn-primary !py-2">ورود</a>
            @endauth
        </div>
    </div>
</header>

<main class="min-h-[calc(100vh-4rem)]">
    {{ $slot }}
</main>

<footer class="border-t border-gray-100 bg-gray-50 py-10">
    <div class="mx-auto max-w-6xl px-4 text-center text-sm text-gray-400">
        <p>آدینت — اتصال موکلان به وکلای معتبر</p>
    </div>
</footer>

@livewireScripts
</body>
</html>
