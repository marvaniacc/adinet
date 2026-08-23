<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use Illuminate\Support\Str;

/**
 * Deterministic gateway for development/demo environments.
 * The "redirect URL" is an in-app simulation page whose buttons
 * drive the same callback pipeline a real gateway would.
 */
class FakeGateway implements PaymentGateway
{
    public function start(int $amountToman, string $callbackUrl, string $description): array
    {
        $authority = 'FAKE-'.Str::upper(Str::random(16));

        return [
            'authority' => $authority,
            'redirect_url' => route('payments.fake', ['authority' => $authority]),
        ];
    }

    public function verify(string $authority, int $amountToman): VerifyResult
    {
        // Fake authority tokens starting with FAKE-FAIL simulate failure.
        if (str_contains($authority, 'FAIL')) {
            return new VerifyResult(success: false, message: 'پرداخت آزمایشی ناموفق');
        }

        return new VerifyResult(success: true, refId: 'FAKE-REF-'.Str::upper(Str::random(8)));
    }
}
