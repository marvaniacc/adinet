<?php

namespace App\Contracts;

/**
 * Delivery channel dedicated to OTP codes. Implemented alongside
 * SmsProvider by drivers capable of template/OTP-grade messaging.
 */
interface OtpSender
{
    /** Deliver an OTP code to an Iranian mobile (canonical 09xxxxxxxxx). */
    public function sendOtp(string $mobile, string $code): void;
}
