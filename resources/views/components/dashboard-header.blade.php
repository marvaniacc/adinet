<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-center justify-between gap-3']) }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-bold text-gray-900">{{ $title }}</h1>
        @isset($subtitle)
            <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
        @endisset
    </div>
    @isset($actions)
        <div class="flex flex-none flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>

@if (session('status'))
    <div class="mb-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
@endif
