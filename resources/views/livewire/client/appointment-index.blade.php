<div>
    <x-dashboard-header title="نوبت‌های من" subtitle="نوبت‌های مشاوره هماهنگ‌شده با وکلا."></x-dashboard-header>
    
    @if (session('error'))
        <div class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="mt-6 space-y-3 pb-8">
        @forelse ($appointments as $appointment)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm {{ $appointment->status->value !== 'scheduled' ? 'opacity-75' : '' }}" wire:key="ap-{{ $appointment->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('lawyers.show', $appointment->lawyerProfile->slug) }}" target="_blank" class="font-semibold text-gray-900 hover:text-brand-700">
                                {{ $appointment->lawyerProfile->display_name }} ↗
                            </a>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $appointment->status->color() }}">
                                {{ $appointment->status->label() }}
                            </span>
                        </div>

                        @if ($appointment->service)
                            <p class="mt-1 text-sm text-gray-600">{{ $appointment->service->title }}</p>
                        @endif

                        <p class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                            <span class="inline-flex items-center gap-1">
                                <span class="material-symbols-rounded text-sm text-brand-600">event</span>
                                {{ \App\Support\PersianDate::format($appointment->scheduled_at, withTime: true) }}
                            </span>
                            <span>{{ \App\Support\PersianDate::digits($appointment->duration_minutes) }} دقیقه</span>
                            <span>{{ $appointment->consultation_type->label() }}</span>
                        </p>

                        @if ($appointment->notes)
                            <p class="mt-2 rounded-lg bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-500">یادداشت وکیل: {{ $appointment->notes }}</p>
                        @endif

                        @if ($appointment->status === \App\Enums\AppointmentStatus::Completed)
                            @if ($appointment->consultationRequest?->review)
                                <p class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-green-600">
                                    <span class="material-symbols-rounded text-sm">check_circle</span>
                                    نظر شما ثبت شده است
                                </p>
                            @else
                                <a href="{{ route('reviews.create', $appointment->consultation_request_id) }}"
                                   class="mt-2 inline-flex items-center gap-1 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100">
                                    <span class="material-symbols-rounded text-sm">rate_review</span>
                                    ثبت نظر درباره این مشاوره
                                </a>
                            @endif
                        @endif
                    </div>

                    <div class="flex shrink-0 flex-col items-end gap-2">
                        @php($needsPayment = $appointment->status === \App\Enums\AppointmentStatus::Scheduled
                            && (int) ($appointment->service?->price_amount_minor ?? 0) > 0
                            && ($appointment->payment?->status) !== \App\Enums\PaymentStatus::Paid)

                        @if ($needsPayment)
                            <a href="{{ route('payments.start', $appointment) }}"
                               class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-amber-600">
                                <span class="material-symbols-rounded text-base">credit_card</span>
                                پرداخت آنلاین ({{ number_format($appointment->service->price_amount_minor) }} تومان)
                            </a>
                            <span class="text-[10px] text-gray-400">تأیید نهایی نوبت پس از پرداخت</span>
                        @elseif ($appointment->payment?->status === \App\Enums\PaymentStatus::Paid)
                            <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-0.5 text-[11px] font-medium text-green-700 ring-1 ring-inset ring-green-200">
                                <span class="material-symbols-rounded text-sm">check_circle</span>
                                پرداخت‌شده{{ $appointment->payment->ref_id ? ' · '.\App\Support\PersianDate::digits($appointment->payment->ref_id) : '' }}
                            </span>
                        @endif

                        @if ($appointment->status === \App\Enums\AppointmentStatus::Scheduled)
                            @if ($needsPayment || ! $appointment->payment || $appointment->payment->status !== \App\Enums\PaymentStatus::Paid)
                                <button type="button" wire:click="cancel({{ $appointment->id }})"
                                        wire:confirm="این نوبت لغو شود؟"
                                        class="rounded-lg px-3 py-2 text-xs font-medium text-gray-500 hover:bg-red-50 hover:text-red-600">
                                    لغو نوبت
                                </button>
                            @else
                                @php($supportMobile = \App\Models\Setting::get('support_mobile'))
<span class="max-w-40 rounded-lg bg-gray-50 px-3 py-2 text-center text-[10px] leading-relaxed text-gray-400">
                                برای لغو با پشتیبانی تماس بگیرید
                                @if ($supportMobile)<br><span dir="ltr">{{ $supportMobile }}</span>@endif
                            </span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-14 text-center">
                <span class="material-symbols-rounded mx-auto block text-4xl text-gray-300">event_busy</span>
                <p class="mt-3 font-medium text-gray-900">نوبتی وجود ندارد</p>
                <p class="mt-1 text-sm text-gray-500">پس از پذیرش درخواست مشاوره توسط وکیل، نوبت شما اینجا نمایش داده می‌شود.</p>
                <a href="{{ route('lawyers.index') }}" class="btn-primary mt-5">مشاهده وکلا</a>
            </div>
        @endforelse
    </div>

    {{ $appointments->links() }}
</div>
