<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Enums\ConsultationRequestStatus;
use App\Models\ConsultationRequest;
use Illuminate\Notifications\Notification;

class ConsultationRequestDecided extends Notification
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
        $lawyer = $this->request->lawyerProfile->display_name;

        if ($this->request->status === ConsultationRequestStatus::Accepted) {
            $when = $this->request->appointment
                ? ' - زمان: '.$this->request->appointment->scheduled_at->format('Y-m-d H:i')
                : '';

            return "آدینت - وکیل «{$lawyer}» درخواست مشاوره شما را پذیرفت{$when}. جزئیات در داشبورد موکل.";
        }

        return "آدینت - وکیل «{$lawyer}» درخواست مشاوره شما را رد کرد. می‌توانید به وکلای دیگر مراجعه کنید.";
    }
}
