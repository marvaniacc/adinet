<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>درگاه پرداخت آزمایشی | آدینت</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
<div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-8 shadow-sm text-center">
    <span class="material-symbols-rounded mx-auto block text-4xl text-brand-600">account_balance</span>
    <h1 class="mt-3 font-bold text-gray-900">درگاه پرداخت آزمایشی (حالت fake)</h1>
    <p class="mt-2 text-sm text-gray-500">این صفحه فقط در محیط توسعه نمایش داده می‌شود. درگاه واقعی زرین‌پال با تنظیم ZARINPAL_MODE فعال می‌گردد.</p>
    <p dir="ltr" class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-400">{{ $authority }}</p>

    <div class="mt-6 grid grid-cols-2 gap-3">
        <a href="{{ route('payments.callback', ['Authority' => $authority, 'Status' => 'OK']) }}"
           onclick="event.preventDefault(); window.location.href = this.href + '&' + new URLSearchParams(window.location.search);"
           class="btn-primary w-full">پرداخت موفق</a>
        <a href="{{ route('payments.callback', ['Authority' => $authority, 'Status' => 'NOK']) }}" class="btn-secondary w-full !text-red-600 !border-red-200 hover:!bg-red-50">لغو / ناموفق</a>
    </div>
    <p class="mt-4 text-[11px] leading-relaxed text-gray-400">پرداخت موفق با کد پیگیری آزمایشی تأیید می‌شود؛ لغو، وضعیت نوبت را «ناموفق» ثبت می‌کند.</p>
</div>
@livewireScripts
</body>
</html>
