<?php

namespace App\Channels;

use App\Contracts\SmsProvider;
use Illuminate\Notifications\Notification;
use RuntimeException;

/**
 * Delivers a notification as an SMS through the configured provider.
 * The notification must implement toSms($notifiable): string.
 */
class SmsChannel
{
    public function __construct(
        private readonly SmsProvider $sms,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $mobile = $notifiable->mobile ?? null;

        if (! is_string($mobile) || $mobile === '') {
            return; // Nothing sensible to send to; never block the business flow.
        }

        $message = $notification->toSms($notifiable);

        if (! is_string($message) || $message === '') {
            throw new RuntimeException('SMS notification must return a non-empty message.');
        }

        // Delivery failures are logged by the provider layer but must not
        // roll back the business transaction that triggered the notify.
        try {
            $this->sms->send($mobile, $message);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
