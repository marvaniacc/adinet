<div>
    <h1 class="text-2xl font-bold text-gray-900">پرداخت‌ها</h1>
    <p class="mt-1 text-sm text-gray-500">تراکنش‌های ثبت‌شده از طریق درگاه زرین‌پال.</p>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs text-gray-400">تعداد پرداخت‌های موفق</p>
            <p class="mt-1 text-xl font-extrabold text-gray-900">{{ \App\Support\PersianDate::digits($totals['paid_count']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs text-gray-400">مجموع مبالغ موفق</p>
            <p class="mt-1 text-xl font-extrabold text-gray-900">{{ number_format($totals['paid_sum']) }} تومان</p>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500">
                <tr>
                    <th class="px-5 py-3 text-right font-medium">موکل</th>
                    <th class="px-5 py-3 text-right font-medium">نوبت</th>
                    <th class="px-5 py-3 text-right font-medium">مبلغ</th>
                    <th class="px-5 py-3 text-right font-medium">وضعیت</th>
                    <th class="px-5 py-3 text-right font-medium">کد پیگیری</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($payments as $payment)
                    <tr wire:key="pay-{{ $payment->id }}">
                        <td class="px-5 py-3">
                            <span class="font-medium text-gray-900">{{ $payment->client?->fullName() }}</span>
                            <span dir="ltr" class="block text-[11px] text-gray-400">{{ $payment->client?->mobile }}</span>
                        </td>
                        <td class="px-5 py-3 text-xs text-gray-600">
                            @if ($payment->appointment)
                                #{{ \App\Support\PersianDate::digits($payment->appointment->id) }}
                                · {{ \App\Support\PersianDate::format($payment->appointment->scheduled_at, withTime: true) }}
                            @endif
                        </td>
                        <td class="px-5 py-3 font-medium text-gray-900">{{ number_format($payment->amount_toman) }} تومان</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $payment->status->color() }}">
                                {{ $payment->status->label() }}
                            </span>
                        </td>
                        <td dir="ltr" class="px-5 py-3 text-left text-xs text-gray-500">{{ $payment->ref_id ?? '—' }}</td>
                        <td class="px-5 py-3">
                            @if ($payment->status === \App\Enums\PaymentStatus::RefundRequested)
                                <button type="button" wire:click="markRefunded({{ $payment->id }})"
                                        wire:target="markRefunded({{ $payment->id }})" wire:loading.attr="disabled"
                                        wire:confirm="بازگشت وجه به موکل انجام شد؟"
                                        class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50">
                                    بازگشت ثبت شد
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400">هنوز پرداختی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $payments->links() }}
</div>
