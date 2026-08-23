<?php

namespace App\Contracts;

interface SmsProvider
{
    /**
     * Send a text message to an Iranian mobile number (canonical 09xxxxxxxxx).
     *
     * @throws \RuntimeException when delivery fails
     */
    public function send(string $mobile, string $message): void;
}
