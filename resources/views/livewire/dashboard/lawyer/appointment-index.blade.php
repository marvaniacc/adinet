<div>
    <h1 class="text-2xl font-bold text-gray-900">نوبت‌ها</h1>
    <p class="mt-1 text-sm text-gray-500">مدیریت نوبت‌های مشاوره هماهنگ‌شده.</p>

    @if (session('status'))
        <div class="mt-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <div class="mt-6 space-y-3 pb-8">
        @forelse ($appointments as $appointment)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm {{ $appointment->status->value !== 'scheduled' ? 'opacity-75' : '' }}" wire:key="ap-{{ $appointment->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-gray-900">{{ $appointment->client->fullName() }}</span>
                            <span dir="ltr" class="text-xs text-gray-400">{{ $appointment->client->mobile }}</span>
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
                            <p class="mt-2 rounded-lg bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-500">یادداشت: {{ $appointment->notes }}</p>
                        @endif
                    </div>

                    @if ($appointment->status === \App\Enums\AppointmentStatus::Scheduled)
                        <div class="flex shrink-0 flex-wrap gap-1.5">
                            <button type="button" wire:click="mark({{ $appointment->id }}, 'completed')"
                                    class="rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-700">برگزار شد</button>
                            <button type="button" wire:click="mark({{ $appointment->id }}, 'no_show')"
                                    wire:confirm="موکل در جلسه حضور نداشت؟"
                                    class="rounded-lg bg-gray-100 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-200">بدون حضور</button>
                            <button type="button" wire:click="mark({{ $appointment->id }}, 'cancelled')"
                                    wire:confirm="این نوبت لغو شود؟"
                                    class="rounded-lg px-3 py-2 text-xs font-medium text-red-500 hover:bg-red-50 hover:text-red-600">لغو</button>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-14 text-center">
                <span class="material-symbols-rounded mx-auto block text-4xl text-gray-300">event_busy</span>
                <p class="mt-3 font-medium text-gray-900">نوبتی ثبت نشده است</p>
                <p class="mt-1 text-sm text-gray-500">با پذیرش درخواست مشاوره، نوبت‌ها به‌صورت خودکار اینجا ساخته می‌شوند.</p>
            </div>
        @endforelse
    </div>

    {{ $appointments->links() }}
</div>
