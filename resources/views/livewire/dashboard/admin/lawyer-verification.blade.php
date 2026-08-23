<div>
    <h1 class="text-2xl font-bold text-gray-900">تأیید وکلا</h1>
    <p class="mt-1 text-sm text-gray-500">بررسی و تأیید پروفایل‌های ارسال‌شده توسط وکلا.</p>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    {{-- Status tabs --}}
    <div class="mt-5 flex flex-wrap gap-2" role="group" aria-label="فیلتر وضعیت وکلا">
        @foreach ($statuses as $s)
            @php($selected = $currentStatus->value === $s->value)
            <button type="button"
                    wire:click="$set('status', '{{ $s->value }}')"
                    aria-pressed="{{ $selected ? 'true' : 'false' }}"
                    wire:key="tab-{{ $s->value }}"
                    class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-medium transition
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1
                           {{ $selected
                               ? 'border-brand-600 bg-brand-600 text-white shadow-sm'
                               : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50' }}">
                {{ $s->label() }}
                <span class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] leading-4
                             {{ $selected ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' }}">
                    {{ \App\Support\PersianDate::digits($counts[$s->value] ?? 0) }}
                </span>
            </button>
        @endforeach
    </div>

    {{-- List --}}
    <div class="mt-6 space-y-4 pb-8">
        @forelse ($profiles as $profile)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" wire:key="lp-{{ $profile->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('lawyers.show', $profile->slug) }}" target="_blank" class="font-semibold text-gray-900 hover:text-brand-700">
                                {{ $profile->display_name }} ↗
                            </a>
                            <span class="text-xs text-gray-400">{{ $profile->city?->name }}</span>
                        </div>
                        <p class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                            <span>پروانه: <b dir="ltr">{{ $profile->license_number }}</b></span>
                            <span>{{ $profile->bar_association }}</span>
                            <span>{{ $profile->years_of_experience }} سال سابقه</span>
                        </p>
                        <p class="mt-1.5 flex flex-wrap items-center gap-x-3 text-xs text-gray-400">
                            <span dir="ltr">{{ $profile->user?->mobile }}</span>
                            @if ($profile->submitted_for_review_at)
                                <span>ارسال: {{ \App\Support\PersianDate::format($profile->submitted_for_review_at) }}</span>
                            @endif
                        </p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($profile->specialties as $specialty)
                                <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-700">{{ $specialty->name }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        @if ($profile->status === \App\Enums\LawyerStatus::PendingReview)
                            <button type="button" wire:click="verify({{ $profile->id }})" wire:loading.attr="disabled"
                                    wire:target="verify({{ $profile->id }})"
                                    class="inline-flex items-center gap-1 rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white hover:bg-green-700 disabled:opacity-50">
                                <span class="material-symbols-rounded text-sm">check</span> تأیید
                            </button>
                            <button type="button" wire:click="openReject({{ $profile->id }})"
                                    class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-100">
                                <span class="material-symbols-rounded text-sm">close</span> رد
                            </button>
                            <button type="button" wire:click="suspend({{ $profile->id }})"
                                    class="rounded-lg px-3 py-2 text-xs font-medium text-gray-500 hover:bg-gray-100">معلق</button>
                        @elseif ($profile->status === \App\Enums\LawyerStatus::Suspended)
                            <button type="button" wire:click="reinstate({{ $profile->id }})"
                                    class="inline-flex items-center gap-1 rounded-lg bg-green-50 px-3 py-2 text-xs font-semibold text-green-700 hover:bg-green-100">
                                فعال‌سازی مجدد
                            </button>
                        @elseif ($profile->status === \App\Enums\LawyerStatus::Verified)
                            <button type="button" wire:click="suspend({{ $profile->id }})"
                                    wire:confirm="این وکیل از نمایش عمومی حذف و معلق شود؟"
                                    class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-100">
                                <span class="material-symbols-rounded text-sm">pause_circle</span> تعلیق
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Inline rejection form --}}
                @if ($rejectingId === $profile->id)
                    <form wire:submit="reject({{ $profile->id }})" class="mt-4 rounded-xl border border-red-200 bg-red-50/60 p-4">
                        <label class="mb-1.5 block text-sm font-medium text-red-800">دلیل رد (برای وکیل نمایش داده می‌شود) *</label>
                        <textarea rows="2" class="input !border-red-200" wire:model="rejection_reason"
                                  placeholder="مثلاً: شماره پروانه قابل استعلام نیست؛ لطفاً تصویر پروانه را اصلاح کنید."></textarea>
                        @error('rejection_reason') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                        <div class="mt-3 flex gap-2">
                            <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">ثبت رد</button>
                            <button type="button" wire:click="cancelReject" class="rounded-lg bg-white px-4 py-2 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50">انصراف</button>
                        </div>
                    </form>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-14 text-center">
                <span class="material-symbols-rounded mx-auto block text-4xl text-gray-300">inbox</span>
                <p class="mt-3 text-sm text-gray-500">موردی در این وضعیت وجود ندارد.</p>
            </div>
        @endforelse
    </div>

    {{ $profiles->links() }}
</div>
