<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /** Client initiates payment for their own scheduled appointment. */
    public function start(Appointment $appointment, PaymentGateway $gateway)
    {
        $this->authorizeClient($appointment);

        abort_unless($appointment->status === AppointmentStatus::Scheduled, 404);

        $amount = (int) ($appointment->service?->price_amount_minor ?? 0);

        if ($amount <= 0) {
            return redirect()->route('dashboard.appointments')
                ->with('status', 'این نوبت نیاز به پرداخت ندارد.');
        }

        // Already paid?
        if (($appointment->payment?->status) === PaymentStatus::Paid) {
            return redirect()->route('dashboard.appointments')
                ->with('status', 'این نوبت قبلاً پرداخت شده است.');
        }

        try {
            $result = $gateway->start(
                amountToman: $amount,
                callbackUrl: route('payments.callback'),
                description: 'پرداخت نوبت مشاوره آدینت - '.$appointment->service->title,
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('dashboard.appointments')
                ->with('error', $e->getMessage() ?: 'اتصال به درگاه پرداخت ممکن نشد. لطفاً بعداً تلاش کنید.');
        }

        DB::transaction(function () use ($appointment, $result, $amount) {
            Payment::updateOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'client_id' => Auth::id(),
                    'amount_toman' => $amount,
                    'authority' => $result['authority'],
                    'status' => PaymentStatus::Redirected,
                ],
            );
        });

        return redirect()->away($result['redirect_url']);
    }

    /**
     * Gateway returns the user's browser here. No auth middleware:
     * identity is bound to the secret authority token and the amount
     * is re-verified server-side against ZarinPal.
     */
    public function callback(Request $request, PaymentGateway $gateway)
    {
        $request->validate([
            'Authority' => ['required', 'string', 'max:120'],
            'Status' => ['required', 'in:OK,NOK'],
        ]);

        $payment = Payment::query()->where('authority', (string) $request->input('Authority'))->firstOrFail();
        $appointment = $payment->appointment;

        if ($payment->status === PaymentStatus::Paid) {
            return redirect()->route('dashboard.appointments')->with('status', 'پرداخت شما قبلاً تأیید شده است.');
        }

        if ((string) $request->input('Status') !== 'OK') {
            $payment->forceFill(['status' => PaymentStatus::Failed])->save();

            return redirect()->route('dashboard.appointments')->with('error', 'پرداخت توسط شما لغو شد یا ناموفق بود.');
        }

        $verify = $gateway->verify($payment->authority, $payment->amount_toman);

        if (! $verify->success) {
            $payment->forceFill(['status' => PaymentStatus::Failed])->save();

            return redirect()->route('dashboard.appointments')->with('error', $verify->message ?? 'تأیید پرداخت ناموفق بود.');
        }

        DB::transaction(function () use ($payment, $verify) {
            $payment->forceFill([
                'status' => PaymentStatus::Paid,
                'ref_id' => $verify->refId,
                'paid_at' => now(),
            ])->save();

            $payment->appointment->forceFill(['notes' => trim(($payment->appointment->notes ?? '')."\n"."پرداخت تأیید شد (کد پیگیری: {$verify->refId})")])->save();
        });

        return redirect()->route('dashboard.appointments')->with('status', "پرداخت با موفقیت انجام شد. کد پیگیری: {$verify->refId}");
    }

    /** Simulated gateway page - only exists while PAYMENT mode is fake. */
    public function fakePage(string $authority)
    {
        abort_unless(config('services.zarinpal.mode') === 'fake', 404);

        return view('payments.fake-gateway', ['authority' => $authority]);
    }

    private function authorizeClient(Appointment $appointment): void
    {
        abort_unless(Auth::id() === $appointment->client_id, 403);
    }
}
