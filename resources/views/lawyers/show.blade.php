<x-app-layout :title="$profile->display_name.' | آدینت'">
    <div class="mx-auto max-w-5xl px-4 py-10">
        @if (! $profile->isVerified() && $isOwner)
            <div class="mb-6 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-700">
                این پیش‌نمایش خصوصی پروفایل شماست — تا زمان تأیید، برای عموم نمایش داده نمی‌شود.
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
            {{-- Main column --}}
            <div>
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                        @if ($profile->profile_photo)
                            <img src="{{ Storage::url($profile->profile_photo) }}" class="h-24 w-24 rounded-full object-cover ring-4 ring-brand-50" alt="">
                        @else
                            <span class="flex h-24 w-24 items-center justify-center rounded-full bg-brand-50 text-brand-600 ring-4 ring-brand-50">
                                <span class="material-symbols-outlined text-5xl">person</span>
                            </span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-2xl font-extrabold text-gray-900">{{ $profile->display_name }}</h1>
                                @if ($profile->isVerified())
                                    <span title="تأییدشده توسط آدینت" class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-200">
                                        <span class="material-symbols-outlined text-sm">verified</span>
                                        تأییدشده
                                    </span>
                                @endif
                                @if ($reviewsCount > 0)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200">
                                        <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                                        {{ \App\Support\PersianDate::digits($avgRating) }}
                                        <span class="text-amber-400/70">({{ \App\Support\PersianDate::digits($reviewsCount) }})</span>
                                    </span>
                                @endif
                            </div>

                            <p class="mt-2 text-sm text-gray-500">
                                {{ $profile->city?->name ?? '—' }}
                                · {{ $profile->years_of_experience }} سال سابقه
                                @if ($profile->bar_association)
                                    · {{ $profile->bar_association }}
                                @endif
                            </p>

                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($profile->specialties as $specialty)
                                    <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-100">{{ $specialty->name }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    @if ($profile->bio)
                        <div class="mt-6 border-t border-gray-100 pt-6">
                            <h2 class="font-semibold text-gray-900">درباره</h2>
                            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-gray-600">{{ $profile->bio }}</p>
                        </div>
                    @endif
                </div>

                {{-- Reviews --}}
                <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="font-semibold text-gray-900">نظرات موکلان</h2>

                    <div class="mt-4 space-y-3">
                        @forelse ($reviews as $review)
                            <div class="rounded-xl border border-gray-100 bg-gray-50/60 px-4 py-3.5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="inline-flex items-center gap-0.5 text-amber-500" aria-label="{{ $review->rating }} از ۵">
                                        @for ($i = 0; $i < $review->rating; $i++)
                                            <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' 1;">star</span>
                                        @endfor
                                    </span>
                                    <span class="text-[11px] text-gray-400">{{ \App\Support\PersianDate::format($review->created_at) }}</span>
                                </div>
                                @if ($review->comment)
                                    <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $review->comment }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-400">هنوز نظری ثبت نشده است.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Services --}}
                <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="font-semibold text-gray-900">خدمات مشاوره</h2>

                    <div class="mt-4 space-y-3">
                        @forelse ($services as $service)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-100 bg-gray-50/60 px-4 py-3.5">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $service->title }}</p>
                                    <p class="mt-0.5 flex flex-wrap items-center gap-x-3 text-xs text-gray-500">
                                        <span>{{ $service->consultation_type->label() }}</span>
                                        <span>· {{ $service->duration_minutes }} دقیقه</span>
                                        @if ($service->priceLabel())
                                            <span>· {{ $service->priceLabel() }}</span>
                                        @endif
                                    </p>
                                </div>
                                <a href="{{ request('consult') ? '#' : route('lawyers.show', $profile->slug) }}#consult"
                                   class="btn-secondary !px-4 !py-2 !text-xs">درخواست مشاوره</a>
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-400">هنوز خدمتی ثبت نشده است.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <aside class="space-y-4 lg:sticky lg:top-20 lg:self-start" id="consult">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    @auth
                        @if (auth()->user()->isClient())
                            <a href="{{ route('lawyers.request.create', $profile->slug) }}" class="btn-primary w-full py-3 text-base">
                                درخواست مشاوره
                            </a>
                            <p class="mt-3 text-center text-xs text-gray-400">پس از ثبت درخواست، وکیل در اسرع وقت پاسخ می‌دهد.</p>
                        @else
                            <span class="btn-primary w-full cursor-not-allowed py-3 text-base opacity-60" aria-disabled="true">
                                درخواست مشاوره
                            </span>
                            <p class="mt-3 text-center text-xs text-gray-400">ثبت درخواست مخصوص موکلان است.</p>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn-primary w-full py-3 text-base">ورود و درخواست مشاوره</a>
                        <p class="mt-3 text-center text-xs text-gray-400">برای ارسال درخواست، ابتدا وارد حساب خود شوید.</p>
                    @endauth
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-900">اطلاعات حرفه‌ای</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="shrink-0 text-gray-500">شماره پروانه</dt>
                            <dd dir="ltr" class="truncate font-medium text-gray-800">{{ $profile->license_number ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="shrink-0 text-gray-500">مرجع صلاحیت‌دار</dt>
                            <dd class="truncate font-medium text-gray-800">{{ $profile->bar_association ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="shrink-0 text-gray-500">شهر</dt>
                            <dd class="font-medium text-gray-800">{{ $profile->city?->name ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
