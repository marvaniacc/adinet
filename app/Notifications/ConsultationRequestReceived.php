<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Models\ConsultationRequest;
use Illuminate\Notifications\Notification;

class ConsultationRequestReceived extends Notification
{
    public function __construct(
        public readonly ConsultationRequest $request,
    ) {}

    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        $clientName = $this->request->client->fullName();

        return "آدینت - درخواست مشاوره جدید از «{$clientName}» با موضوع «{$this->request->subject}». لطفاً در پنل وکیل پاسخ دهید.";
    }
}
