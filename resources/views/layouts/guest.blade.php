<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ورود | آدینت' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 antialiased">
<div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
    <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2 text-2xl font-extrabold text-brand-700">
        <span class="material-symbols-rounded text-brand-600">balance</span>
        آدینت
    </a>

    <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
        {{ $slot }}
    </div>

    <p class="mt-8 text-center text-xs text-gray-400">
        با ورود، <a href="#" class="underline hover:text-gray-600">شرایط استفاده</a> و
        <a href="#" class="underline hover:text-gray-600">حریم خصوصی</a> آدینت را می‌پذیرید.
    </p>
</div>
@livewireScripts
</body>
</html>
