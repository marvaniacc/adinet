<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Real ZarinPal v4 REST integration.
 *
 * Amounts: the gateway expects Rials; Adinet stores Toman, so we
 * multiply by 10 at the boundary (see TOMAN_TO_RIAL).
 *
 * Modes: live = payment.zarinpal.com, sandbox = sandbox.zarinpal.com.
 */
class ZarinpalGateway implements PaymentGateway
{
    public const TOMAN_TO_RIAL = 10;

    public function __construct(
        private readonly string $merchantId,
        private readonly string $mode = 'live',
    ) {}

    private function base(): string
    {
        return $this->mode === 'sandbox'
            ? 'https://sandbox.zarinpal.com'
            : 'https://payment.zarinpal.com';
    }

    public function start(int $amountToman, string $callbackUrl, string $description): array
    {
        if ($this->merchantId === '') {
            throw new RuntimeException('درگاه پرداخت هنوز تنظیم نشده است.');
        }

        $response = Http::asJson()->timeout(15)->post($this->base().'/pg/v4/payment/request.json', [
            'merchant_id' => $this->merchantId,
            'amount' => $amountToman * self::TOMAN_TO_RIAL,
            'callback_url' => $callbackUrl,
            'description' => mb_substr($description, 0, 255),
        ])->throw();

        $data = $response->json('data') ?? [];
        $errors = $response->json('errors');

        if (($data['authority'] ?? null) === null || ! empty($errors)) {
            $message = is_array($errors) && $errors !== []
                ? ($errors['message'] ?? 'خطای درگاه پرداخت')
                : 'درگاه پرداخت درخواست را نپذیرفت';

            throw new RuntimeException((string) $message);
        }

        return [
            'authority' => (string) $data['authority'],
            'redirect_url' => $this->base().'/pg/StartPay/'.$data['authority'],
        ];
    }

    public function verify(string $authority, int $amountToman): VerifyResult
    {
        $response = Http::asJson()->timeout(15)->post($this->base().'/pg/v4/payment/verify.json', [
            'merchant_id' => $this->merchantId,
            'amount' => $amountToman * self::TOMAN_TO_RIAL,
            'authority' => $authority,
        ]);

        $code = (int) ($response->json('data.code') ?? 0);
        $refId = isset($response->json('data')['ref_id']) ? (string) $response->json('data.ref_id') : null;

        // 100 = verified now, 101 = verified before (double callback is OK).
        if (in_array($code, [100, 101], true)) {
            return new VerifyResult(success: true, refId: $refId);
        }

        return new VerifyResult(
            success: false,
            message: match ($code) {
                -9 => 'خطای اعتبارسنجی درگاه',
                -50 => 'مبلغ پرداخت‌شده با مبلغ نوبت هم‌خوانی ندارد',
                -51 => 'پرداخت ناموفق یا لغو شده',
                -53 => 'پرداخت به غیر وکیل مربوط است',
                default => 'تأیید پرداخت ناموفق بود (کد '.$code.')',
            }
        );
    }
}
