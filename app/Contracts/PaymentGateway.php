<?php

namespace App\Contracts;

use App\Services\Payment\VerifyResult;

interface PaymentGateway
{
    /**
     * Initiate a payment for the given amount.
     *
     * @param  int  $amountToman  Amount in Toman (display currency).
     * @param  string  $callbackUrl  Absolute URL the gateway redirects back to.
     * @return array{authority: string, redirect_url: string}
     *
     * @throws \RuntimeException on gateway rejection/failure
     */
    public function start(int $amountToman, string $callbackUrl, string $description): array;

    /** Verify a returned authority. Code 101 (already verified) counts as success. */
    public function verify(string $authority, int $amountToman): VerifyResult;
}
